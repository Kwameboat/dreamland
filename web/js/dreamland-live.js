/**
 * Dreamland live WebRTC client — CDN libs load only when going live / watching.
 */

function emitAck(socket, event, payload = {}, timeoutMs = 15000) {
  return new Promise((resolve, reject) => {
    const timer = setTimeout(() => reject(new Error(`${event} timed out`)), timeoutMs);
    socket.emit(event, payload, (res) => {
      clearTimeout(timer);
      if (res?.ok) resolve(res);
      else reject(new Error(res?.message || `${event} failed`));
    });
  });
}

function withTimeout(promise, ms, message) {
  return Promise.race([
    promise,
    new Promise((_, reject) => {
      window.setTimeout(() => reject(new Error(message)), ms);
    }),
  ]);
}

function waitForTransportConnection(transport, timeoutMs = 22000) {
  if (transport.connectionState === 'connected') return Promise.resolve();
  return new Promise((resolve, reject) => {
    const timer = window.setTimeout(() => {
      cleanup();
      reject(new Error('Live video connection timed out'));
    }, timeoutMs);
    const onState = () => {
      const state = transport.connectionState;
      if (state === 'connected') {
        cleanup();
        resolve();
      } else if (state === 'failed' || state === 'closed') {
        cleanup();
        reject(new Error('Live video connection failed'));
      }
    };
    const cleanup = () => {
      window.clearTimeout(timer);
      transport.off('connectionstatechange', onState);
    };
    transport.on('connectionstatechange', onState);
    onState();
  });
}

let libsPromise = null;

async function loadLiveLibs() {
  if (libsPromise) return libsPromise;
  libsPromise = withTimeout(
    Promise.all([
      import('https://cdn.socket.io/4.8.1/socket.io.esm.min.js'),
      import('https://esm.sh/mediasoup-client@3.7.17'),
    ]).then(([socketMod, mediasoupMod]) => ({
      io: socketMod.io,
      mediasoupClient: mediasoupMod,
    })),
    25000,
    'Live libraries failed to load — check your connection',
  ).catch((err) => {
    libsPromise = null;
    throw err;
  });
  return libsPromise;
}

export function createDreamlandLive({ showToast, formatCount } = {}) {
  let broadcastSession = null;
  let watchSession = null;
  let producerPollTimer = null;

  function clearProducerPoll() {
    if (producerPollTimer) {
      window.clearInterval(producerPollTimer);
      producerPollTimer = null;
    }
  }

  async function connectSocket(rtc, role, userId) {
    const { io } = await loadLiveLibs();
    const signalingUrl = String(rtc.signaling_url || '').replace(/\/$/, '');
    if (!signalingUrl) {
      throw new Error('Live signaling URL is missing');
    }
    const isLocalHost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    if (!isLocalHost && /localhost|127\.0\.0\.1/i.test(signalingUrl)) {
      throw new Error('Live signaling is not configured for this site');
    }

    const socket = io(signalingUrl, {
      transports: ['websocket', 'polling'],
      reconnection: true,
      reconnectionAttempts: 8,
    });

    await new Promise((resolve, reject) => {
      const timer = setTimeout(() => reject(new Error('Live signaling timeout')), 12000);
      socket.once('connect', () => {
        clearTimeout(timer);
        resolve();
      });
      socket.once('connect_error', (err) => {
        clearTimeout(timer);
        reject(err);
      });
    });

    const join = await emitAck(socket, 'live:join', {
      liveId: rtc.live_id,
      token: rtc.token,
      role,
      userId: userId || 0,
    });

    return { socket, join };
  }

  async function createSendTransport(socket, device) {
    const info = await emitAck(socket, 'live:createTransport', { direction: 'send' });
    const transport = device.createSendTransport({
      id: info.id,
      iceParameters: info.iceParameters,
      iceCandidates: info.iceCandidates,
      dtlsParameters: info.dtlsParameters,
    });

    transport.on('connect', ({ dtlsParameters }, callback, errback) => {
      emitAck(socket, 'live:connectTransport', {
        transportId: transport.id,
        dtlsParameters,
      }).then(callback).catch(errback);
    });

    transport.on('produce', ({ kind, rtpParameters }, callback, errback) => {
      emitAck(socket, 'live:produce', {
        transportId: transport.id,
        kind,
        rtpParameters,
      })
        .then((res) => callback({ id: res.id }))
        .catch(errback);
    });

    return transport;
  }

  async function createRecvTransport(socket, device) {
    const info = await emitAck(socket, 'live:createTransport', { direction: 'recv' });
    const transport = device.createRecvTransport({
      id: info.id,
      iceParameters: info.iceParameters,
      iceCandidates: info.iceCandidates,
      dtlsParameters: info.dtlsParameters,
    });

    transport.on('connect', ({ dtlsParameters }, callback, errback) => {
      emitAck(socket, 'live:connectTransport', {
        transportId: transport.id,
        dtlsParameters,
      }).then(callback).catch(errback);
    });

    return transport;
  }

  async function produceTracks(transport, stream) {
    const tracks = [];
    for (const track of stream.getTracks()) {
      if (track.readyState !== 'live') continue;
      const params = { track };
      if (track.kind === 'video') {
        params.encodings = [{ maxBitrate: 1_500_000 }];
      }
      tracks.push(await transport.produce(params));
    }
    return tracks;
  }

  async function consumeProducer(socket, device, transport, producerId, remoteStream) {
    const res = await withTimeout(
      emitAck(socket, 'live:consume', {
        transportId: transport.id,
        producerId,
        rtpCapabilities: device.rtpCapabilities,
      }),
      12000,
      'Live consume timed out',
    );

    const consumer = await withTimeout(
      transport.consume({
        id: res.id,
        producerId: res.producerId,
        kind: res.kind,
        rtpParameters: res.rtpParameters,
      }),
      12000,
      'Live media attach timed out',
    );

    await emitAck(socket, 'live:resumeConsumer', { consumerId: consumer.id });
    remoteStream.addTrack(consumer.track);
    return consumer;
  }

  async function attachRemoteStreamToVideo(videoEl, remoteStream) {
    if (!videoEl || !remoteStream) return;
    if (videoEl.srcObject !== remoteStream) {
      videoEl.srcObject = remoteStream;
    }
    videoEl.muted = true;
    videoEl.setAttribute('muted', '');
    videoEl.playsInline = true;
    await videoEl.play().catch(() => {});
    if (remoteStream.getTracks().length) {
      videoEl.closest('.live-watch-visual')?.classList.add('live-watch-visual--playing');
    }
  }

  async function consumeAll(socket, device, transport, videoEl) {
    const list = await emitAck(socket, 'live:getProducers');
    const remoteStream = new MediaStream();
    const consumedProducerIds = new Set();
    const items = list.items || [];
    let consumeAttempted = false;

    for (const item of items) {
      consumeAttempted = true;
      try {
        await consumeProducer(socket, device, transport, item.producerId, remoteStream);
        consumedProducerIds.add(item.producerId);
      } catch (err) {
        console.warn('Consume producer failed:', item.producerId, err.message);
      }
    }

    if (consumeAttempted) {
      await waitForTransportConnection(transport).catch((err) => {
        if (!remoteStream.getTracks().length) throw err;
        console.warn('Transport not fully connected:', err.message);
      });
    }

    if (videoEl) {
      await attachRemoteStreamToVideo(videoEl, remoteStream);
    }

    return { remoteStream, transport, device, consumedProducerIds };
  }

  async function syncProducers(session, callbacks = {}) {
    const { socket, device, recvTransport, videoEl, remoteStream, consumedProducerIds } = session;
    const list = await emitAck(socket, 'live:getProducers');
    let added = false;

    for (const item of list.items || []) {
      if (consumedProducerIds.has(item.producerId)) continue;
      try {
        await consumeProducer(socket, device, recvTransport, item.producerId, remoteStream);
        consumedProducerIds.add(item.producerId);
        added = true;
      } catch (err) {
        console.warn('Sync producer failed:', item.producerId, err.message);
      }
    }

    if (added && videoEl) {
      await attachRemoteStreamToVideo(videoEl, remoteStream);
      callbacks.onStreamReady?.(remoteStream);
    }

    return added;
  }

  function startProducerPolling(session, callbacks = {}) {
    clearProducerPoll();
    let attempts = 0;
    producerPollTimer = window.setInterval(async () => {
      if (!watchSession || watchSession !== session) {
        clearProducerPoll();
        return;
      }
      attempts += 1;
      if (attempts > 20) {
        clearProducerPoll();
        if (!session.remoteStream?.getTracks().length) {
          callbacks.onWaiting?.('Still waiting for the host camera…');
        }
        return;
      }
      try {
        const added = await syncProducers(session, callbacks);
        if (added) clearProducerPoll();
        else if (!session.remoteStream?.getTracks().length) {
          callbacks.onWaiting?.('Waiting for host to start broadcasting…');
        }
      } catch (err) {
        console.warn('Producer poll failed:', err.message);
      }
    }, 2000);
  }

  function clearRemoteStreamTracks(remoteStream) {
    if (!remoteStream) return;
    remoteStream.getTracks().forEach((track) => {
      remoteStream.removeTrack(track);
      try {
        track.stop();
      } catch {
        /* noop */
      }
    });
  }

  async function startBroadcast({ rtc, userId, localStream, onStats, onChat }) {
    await stopBroadcast();

    if (!rtc?.signaling_url) {
      throw new Error('Live RTC config missing');
    }

    const { mediasoupClient } = await loadLiveLibs();
    let stream = localStream;
    if (!stream) {
      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 1280 } },
        audio: true,
      });
    }

    const { socket, join } = await connectSocket(rtc, 'host', userId);
    const device = new mediasoupClient.Device();
    await device.load({ routerRtpCapabilities: join.rtpCapabilities });

    const sendTransport = await createSendTransport(socket, device);
    await produceTracks(sendTransport, stream);
    await waitForTransportConnection(sendTransport).catch((err) => {
      console.warn('Host transport connect:', err.message);
    });

    socket.on('live:stats', (stats) => {
      if (typeof onStats === 'function') onStats(stats);
      else if (stats?.viewerCount != null && formatCount) {
        const el = document.getElementById('live-broadcast-viewers') || document.getElementById('live-watch-viewers');
        if (el) el.textContent = `${formatCount(stats.viewerCount)} watching`;
      }
    });

    socket.on('live:chat', (msg) => {
      if (typeof onChat === 'function') onChat(msg);
    });

    broadcastSession = { socket, device, sendTransport, stream, ownsStream: !localStream };
    showToast?.('Broadcasting live');
    return broadcastSession;
  }

  async function stopBroadcast() {
    if (!broadcastSession) return;
    const { socket, sendTransport, stream, ownsStream } = broadcastSession;
    try { sendTransport.close(); } catch (_) { /* noop */ }
    try { socket.disconnect(); } catch (_) { /* noop */ }
    if (ownsStream) stream.getTracks().forEach((t) => t.stop());
    broadcastSession = null;
  }

  async function startWatching({
    rtc,
    userId,
    videoEl,
    onStats,
    onChat,
    onStreamReady,
    onWaiting,
    onConnected,
  }) {
    await stopWatching();

    if (!rtc?.signaling_url) {
      throw new Error('Live RTC config missing');
    }

    const { mediasoupClient } = await loadLiveLibs();
    const { socket, join } = await connectSocket(rtc, 'viewer', userId);
    onConnected?.();

    const device = new mediasoupClient.Device();
    await device.load({ routerRtpCapabilities: join.rtpCapabilities });
    const recvTransport = await createRecvTransport(socket, device);

    const remoteStream = new MediaStream();
    const session = {
      socket,
      device,
      recvTransport,
      videoEl,
      remoteStream,
      consumedProducerIds: new Set(),
    };

    const consumed = await consumeAll(socket, device, recvTransport, videoEl);
    session.remoteStream = consumed.remoteStream;
    consumed.consumedProducerIds.forEach((id) => session.consumedProducerIds.add(id));

    const callbacks = { onStreamReady, onWaiting };

    if (!session.remoteStream.getTracks().length) {
      onWaiting?.('Waiting for host to start broadcasting…');
      startProducerPolling(session, callbacks);
    } else {
      onStreamReady?.(session.remoteStream);
    }

    socket.on('live:newProducer', async ({ producerId }) => {
      if (!producerId || session.consumedProducerIds.has(producerId)) return;
      try {
        await consumeProducer(socket, device, recvTransport, producerId, session.remoteStream);
        session.consumedProducerIds.add(producerId);
        if (videoEl) {
          await attachRemoteStreamToVideo(videoEl, session.remoteStream);
        }
        onStreamReady?.(session.remoteStream);
        clearProducerPoll();
      } catch (err) {
        console.warn('Consume new producer failed:', err.message);
      }
    });

    socket.on('live:hostReconnecting', () => {
      clearRemoteStreamTracks(session.remoteStream);
      session.consumedProducerIds.clear();
      if (videoEl) {
        videoEl.srcObject = session.remoteStream;
        videoEl.closest('.live-watch-visual')?.classList.remove('live-watch-visual--playing');
      }
      onWaiting?.('Host reconnecting — video will resume shortly…');
      startProducerPolling(session, callbacks);
    });

    socket.on('live:stats', (stats) => {
      if (typeof onStats === 'function') onStats(stats);
      else if (stats?.viewerCount != null && formatCount) {
        const el = document.getElementById('live-watch-viewers');
        if (el) el.textContent = `${formatCount(stats.viewerCount)} watching`;
      }
    });

    socket.on('live:chat', (msg) => {
      if (typeof onChat === 'function') onChat(msg);
    });

    if (join.viewerCount != null) {
      const el = document.getElementById('live-watch-viewers');
      if (el && formatCount) el.textContent = `${formatCount(join.viewerCount)} watching`;
    }

    watchSession = session;
    showToast?.('Connected to live stream');
    return watchSession;
  }

  async function stopWatching() {
    clearProducerPoll();
    if (!watchSession) return;
    const { socket, recvTransport, videoEl, remoteStream } = watchSession;
    try { recvTransport.close(); } catch (_) { /* noop */ }
    try { socket.disconnect(); } catch (_) { /* noop */ }
    clearRemoteStreamTracks(remoteStream);
    if (videoEl) {
      videoEl.srcObject = null;
      videoEl.closest('.live-watch-visual')?.classList.remove('live-watch-visual--playing');
    }
    watchSession = null;
  }

  function sendChat(text) {
    watchSession?.socket?.emit('live:chat', { text });
    broadcastSession?.socket?.emit('live:chat', { text });
  }

  return {
    startBroadcast,
    stopBroadcast,
    startWatching,
    stopWatching,
    sendChat,
    isBroadcasting: () => Boolean(broadcastSession),
    isWatching: () => Boolean(watchSession),
  };
}
