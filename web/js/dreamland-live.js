/**
 * Dreamland live WebRTC client — libs load from same-origin vendor first (CDN fallback).
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

function waitForTransportConnection(transport, timeoutMs = 18000) {
  const state = transport.connectionState;
  if (state === 'connected' || state === 'completed') return Promise.resolve();
  return new Promise((resolve, reject) => {
    const timer = window.setTimeout(() => {
      cleanup();
      reject(new Error('Live video connection timed out'));
    }, timeoutMs);
    const onState = () => {
      const next = transport.connectionState;
      if (next === 'connected' || next === 'completed') {
        cleanup();
        resolve();
      } else if (next === 'failed' || next === 'closed') {
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

function resolveMediasoupModule(mod) {
  const root = mod?.default ?? mod;
  const Device = root?.Device ?? mod?.Device;
  if (!Device) {
    throw new Error('Live video player failed to load');
  }
  return { Device, types: root };
}

async function vendorModuleExists(url) {
  try {
    const res = await withTimeout(fetch(url, { method: 'HEAD', cache: 'no-store' }), 4000, 'vendor check');
    return res.ok;
  } catch {
    return false;
  }
}

async function importLiveModule(urls, label) {
  let lastErr = null;
  for (const url of urls) {
    const timeout = url.startsWith('/') ? 8000 : 20000;
    try {
      if (url.startsWith('/') && !(await vendorModuleExists(url))) {
        continue;
      }
      const mod = await withTimeout(import(/* @vite-ignore */ url), timeout, `${label} timed out`);
      return mod;
    } catch (err) {
      lastErr = err;
      console.warn(`${label} import failed (${url}):`, err.message);
    }
  }
  throw lastErr || new Error(`${label} unavailable`);
}

let socketIoPromise = null;
let mediasoupPromise = null;

async function loadSocketIo() {
  if (socketIoPromise) return socketIoPromise;
  socketIoPromise = importLiveModule([
    '/js/vendor/socket.io.esm.min.js',
    'https://cdn.socket.io/4.8.1/socket.io.esm.min.js',
    'https://cdn.jsdelivr.net/npm/socket.io-client@4.8.1/+esm',
  ], 'Socket.IO').then((mod) => {
    const io = mod.io ?? mod.default?.io ?? mod.default;
    if (!io) throw new Error('Socket.IO client missing');
    return { io };
  }).catch((err) => {
    socketIoPromise = null;
    throw err;
  });
  return socketIoPromise;
}

async function loadMediasoupClient() {
  if (mediasoupPromise) return mediasoupPromise;
  mediasoupPromise = importLiveModule([
    '/js/vendor/mediasoup-client.esm.js',
    'https://cdn.jsdelivr.net/npm/mediasoup-client@3.7.17/+esm',
    'https://esm.sh/mediasoup-client@3.7.17?bundle',
  ], 'Live video player').then(resolveMediasoupModule).catch((err) => {
    mediasoupPromise = null;
    throw err;
  });
  return mediasoupPromise;
}

export async function preloadLiveLibs() {
  await Promise.allSettled([loadSocketIo(), loadMediasoupClient()]);
}

export async function wakeLiveServer(signalingUrl) {
  const base = String(signalingUrl || '').replace(/\/$/, '');
  if (!base) return;
  try {
    await withTimeout(
      fetch(`${base}/health`, { cache: 'no-store', mode: 'cors' }),
      12000,
      'Live server wake timeout',
    );
  } catch {
    /* cold start may still succeed via socket */
  }
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

  async function connectSocket(rtc, role, userId, onStatus) {
    const signalingUrl = String(rtc.signaling_url || '').replace(/\/$/, '');
    if (!signalingUrl) {
      throw new Error('Live signaling URL is missing');
    }
    const isLocalHost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    if (!isLocalHost && /localhost|127\.0\.0\.1/i.test(signalingUrl)) {
      throw new Error('Live signaling is not configured for this site');
    }

    onStatus?.('Waking live video server…');
    await wakeLiveServer(signalingUrl);

    onStatus?.('Loading live player…');
    const { io } = await loadSocketIo();

    onStatus?.('Connecting to live stream…');
    const connectTimeout = /onrender\.com/i.test(signalingUrl) ? 35000 : 15000;
    const socket = io(signalingUrl, {
      transports: ['websocket', 'polling'],
      reconnection: true,
      reconnectionAttempts: 6,
      timeout: connectTimeout,
    });

    await new Promise((resolve, reject) => {
      const timer = setTimeout(() => reject(new Error('Live signaling timeout — try again in a moment')), connectTimeout);
      socket.once('connect', () => {
        clearTimeout(timer);
        resolve();
      });
      socket.once('connect_error', (err) => {
        clearTimeout(timer);
        reject(err instanceof Error ? err : new Error(err?.message || 'Live signaling connection failed'));
      });
    });

    onStatus?.('Joining live room…');
    const join = await emitAck(socket, 'live:join', {
      liveId: rtc.live_id,
      token: rtc.token,
      role,
      userId: userId || 0,
    }, 20000);

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
      tracks.push(await withTimeout(
        transport.produce(params),
        20000,
        `Failed to publish ${track.kind}`,
      ));
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
      15000,
      'Live consume timed out',
    );

    const consumer = await withTimeout(
      transport.consume({
        id: res.id,
        producerId: res.producerId,
        kind: res.kind,
        rtpParameters: res.rtpParameters,
      }),
      15000,
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
    videoEl.setAttribute('playsinline', '');
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

    if (consumeAttempted && remoteStream.getTracks().length) {
      await waitForTransportConnection(transport).catch((err) => {
        console.warn('Transport connect:', err.message);
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
      if (attempts > 25) {
        clearProducerPoll();
        if (!session.remoteStream?.getTracks().length) {
          callbacks.onWaiting?.('Host camera not detected — ask them to restart the broadcast.');
        }
        return;
      }
      try {
        const added = await syncProducers(session, callbacks);
        if (added) clearProducerPoll();
        else if (!session.remoteStream?.getTracks().length) {
          callbacks.onWaiting?.('Waiting for host camera…');
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

  async function startBroadcast({ rtc, userId, localStream, onStats, onChat, onStatus }) {
    await stopBroadcast();

    if (!rtc?.signaling_url) {
      throw new Error('Live RTC config missing');
    }

    let stream = localStream;
    if (!stream) {
      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 1280 } },
        audio: true,
      });
    }

    onStatus?.('Preparing live video…');
    const [{ Device }, { socket, join }] = await Promise.all([
      loadMediasoupClient(),
      connectSocket(rtc, 'host', userId, onStatus),
    ]);

    onStatus?.('Publishing camera…');
    const device = new Device();
    await device.load({ routerRtpCapabilities: join.rtpCapabilities });

    const sendTransport = await createSendTransport(socket, device);
    await produceTracks(sendTransport, stream);
    void waitForTransportConnection(sendTransport).catch((err) => {
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
    onStatus,
  }) {
    await stopWatching();

    if (!rtc?.signaling_url) {
      throw new Error('Live RTC config missing');
    }

    const status = (msg) => {
      if (msg) onStatus?.(msg);
      else onConnected?.();
    };

    const { socket, join } = await connectSocket(rtc, 'viewer', userId, status);
    onConnected?.();

    onStatus?.('Starting video…');
    const { Device } = await loadMediasoupClient();
    const device = new Device();
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

    onStatus?.('Loading live video…');
    const consumed = await consumeAll(socket, device, recvTransport, videoEl);
    session.remoteStream = consumed.remoteStream;
    consumed.consumedProducerIds.forEach((id) => session.consumedProducerIds.add(id));

    const callbacks = { onStreamReady, onWaiting };

    if (!session.remoteStream.getTracks().length) {
      onWaiting?.('Waiting for host camera…');
      startProducerPolling(session, callbacks);
    } else {
      onStatus?.('');
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
        onStatus?.('');
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
