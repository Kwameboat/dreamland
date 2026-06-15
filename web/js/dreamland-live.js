/**
 * Dreamland live WebRTC client — CDN libs load only when going live / watching.
 */

function emitAck(socket, event, payload = {}) {
  return new Promise((resolve, reject) => {
    socket.emit(event, payload, (res) => {
      if (res?.ok) resolve(res);
      else reject(new Error(res?.message || `${event} failed`));
    });
  });
}

let libsPromise = null;

async function loadLiveLibs() {
  if (libsPromise) return libsPromise;
  libsPromise = Promise.all([
    import('https://cdn.socket.io/4.8.1/socket.io.esm.min.js'),
    import('https://esm.sh/mediasoup-client@3.7.17'),
  ]).then(([socketMod, mediasoupMod]) => ({
    io: socketMod.io,
    mediasoupClient: mediasoupMod,
  })).catch((err) => {
    libsPromise = null;
    throw new Error(`Live libraries failed to load: ${err.message}`);
  });
  return libsPromise;
}

export function createDreamlandLive({ showToast, formatCount } = {}) {
  let broadcastSession = null;
  let watchSession = null;

  async function connectSocket(rtc, role, userId) {
    const { io } = await loadLiveLibs();
    const socket = io(rtc.signaling_url, {
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

  async function consumeAll(socket, device, transport, videoEl) {
    const list = await emitAck(socket, 'live:getProducers');
    const remoteStream = new MediaStream();

    for (const item of list.items || []) {
      const res = await emitAck(socket, 'live:consume', {
        transportId: transport.id,
        producerId: item.producerId,
        rtpCapabilities: device.rtpCapabilities,
      });

      const consumer = await transport.consume({
        id: res.id,
        producerId: res.producerId,
        kind: res.kind,
        rtpParameters: res.rtpParameters,
      });

      await emitAck(socket, 'live:resumeConsumer', { consumerId: consumer.id });
      remoteStream.addTrack(consumer.track);
    }

    if (videoEl) {
      videoEl.srcObject = remoteStream;
      videoEl.muted = false;
      videoEl.playsInline = true;
      await videoEl.play().catch(() => {});
      videoEl.closest('.live-watch-visual')?.classList.add('live-watch-visual--playing');
    }

    return { remoteStream, transport, device };
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

  async function startWatching({ rtc, userId, videoEl, onStats, onChat }) {
    await stopWatching();

    if (!rtc?.signaling_url) {
      throw new Error('Live RTC config missing');
    }

    const { mediasoupClient } = await loadLiveLibs();
    const { socket, join } = await connectSocket(rtc, 'viewer', userId);
    const device = new mediasoupClient.Device();
    await device.load({ routerRtpCapabilities: join.rtpCapabilities });
    const recvTransport = await createRecvTransport(socket, device);

    const session = {
      socket,
      device,
      recvTransport,
      videoEl,
      remoteStream: null,
    };

    session.remoteStream = (await consumeAll(socket, device, recvTransport, videoEl)).remoteStream;

    socket.on('live:newProducer', async ({ producerId }) => {
      try {
        const res = await emitAck(socket, 'live:consume', {
          transportId: recvTransport.id,
          producerId,
          rtpCapabilities: device.rtpCapabilities,
        });
        const consumer = await recvTransport.consume({
          id: res.id,
          producerId: res.producerId,
          kind: res.kind,
          rtpParameters: res.rtpParameters,
        });
        await emitAck(socket, 'live:resumeConsumer', { consumerId: consumer.id });
        session.remoteStream.addTrack(consumer.track);
        if (videoEl) await videoEl.play().catch(() => {});
      } catch (err) {
        console.warn('Consume new producer failed:', err.message);
      }
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
    if (!watchSession) return;
    const { socket, recvTransport, videoEl } = watchSession;
    try { recvTransport.close(); } catch (_) { /* noop */ }
    try { socket.disconnect(); } catch (_) { /* noop */ }
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
