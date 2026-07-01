/**
 * Dreamland live WebRTC client — libs load from same-origin vendor first (CDN fallback).
 */

function normalizeRtcConfig(rtc, live = {}) {
  const base = rtc && typeof rtc === 'object' ? { ...rtc } : {};
  return {
    ...base,
    signaling_url: String(base.signaling_url || '').replace(/\/$/, ''),
    signaling_url_direct: String(base.signaling_url_direct || base.signaling_url || '').replace(/\/$/, ''),
    live_id: Number(base.live_id || live.id || 0),
    token: String(base.token || live.token || ''),
    ice_servers: base.ice_servers || base.iceServers || [],
  };
}

function resolveSignalingUrls(cfg) {
  const normalized = normalizeRtcConfig(cfg);
  const urls = [];
  const host = window.location.hostname;
  const onDreamlandProd = /dreamlandgh\.app$/i.test(host) && window.location.protocol === 'https:';
  if (onDreamlandProd) {
    urls.push(`${window.location.origin}/live-socket`);
  }
  for (const candidate of [normalized.signaling_url, normalized.signaling_url_direct]) {
    if (candidate && !urls.includes(candidate)) urls.push(candidate);
  }
  return urls;
}

function isProxiedSignalingUrl(signalingUrl) {
  return /\/live-socket\/?$/i.test(signalingUrl) || String(signalingUrl).includes('/live-socket/');
}

function socketIoOptions(signalingUrl) {
  const proxied = isProxiedSignalingUrl(signalingUrl);
  const connectTimeout = /onrender\.com/i.test(signalingUrl) ? 50000 : 35000;
  return {
    transports: proxied ? ['polling'] : ['polling', 'websocket'],
    upgrade: !proxied,
    withCredentials: false,
    reconnection: false,
    forceNew: true,
    timeout: connectTimeout,
  };
}

function liveAssetStamp() {
  return encodeURIComponent(window.__DL_BUILD__ || `t${Date.now()}`);
}

function resetLiveLibs() {
  socketIoPromise = null;
  mediasoupPromise = null;
}

export async function prepareForLiveConnect() {
  resetLiveLibs();
  try {
    const reg = await navigator.serviceWorker?.getRegistration?.();
    reg?.active?.postMessage?.({ type: 'CLEAR_LIVE_CACHE' });
    navigator.serviceWorker?.controller?.postMessage?.({ type: 'CLEAR_LIVE_CACHE' });
  } catch { /* ignore */ }

  const wakeUrls = [];
  const host = window.location.hostname;
  if (/dreamlandgh\.app$/i.test(host) && window.location.protocol === 'https:') {
    wakeUrls.push(`${window.location.origin}/live-socket`);
  }
  const envSignal = window.__DL_ENV__?.live_signaling_url;
  if (envSignal) wakeUrls.push(String(envSignal).replace(/\/$/, ''));
  await Promise.allSettled([...new Set(wakeUrls)].map((url) => wakeLiveServer(url)));
}

function humanizeSocketError(err) {
  const msg = err?.message || String(err || 'Connection failed');
  if (/timeout/i.test(msg)) {
    return 'Live server is waking up — retrying automatically…';
  }
  if (/xhr poll error|websocket error|poll error|could not connect/i.test(msg)) {
    return 'Reconnecting to live video…';
  }
  return msg;
}
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
  const stamp = liveAssetStamp();
  let lastErr = null;
  for (const url of urls) {
    const busted = url.startsWith('/') && !url.includes('?')
      ? `${url}?v=${stamp}`
      : url;
    const timeout = busted.startsWith('/') ? 8000 : 20000;
    try {
      if (busted.startsWith('/') && !(await vendorModuleExists(busted))) {
        continue;
      }
      const mod = await withTimeout(import(/* @vite-ignore */ busted), timeout, `${label} timed out`);
      return mod;
    } catch (err) {
      lastErr = err;
      console.warn(`${label} import failed (${busted}):`, err.message);
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
  await prepareForLiveConnect();
  await Promise.allSettled([loadSocketIo(), loadMediasoupClient()]);
}

export async function wakeLiveServer(signalingUrl) {
  const base = String(signalingUrl || '').replace(/\/$/, '');
  if (!base) return;
  try {
    await withTimeout(
      fetch(`${base}/health`, { cache: 'no-store', mode: 'cors', credentials: 'omit' }),
      15000,
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

  async function connectSocketAttempt(rtc, role, userId, onStatus) {
    const cfg = normalizeRtcConfig(rtc);
    if (!cfg.live_id || !cfg.token) {
      throw new Error('Live room credentials are missing — refresh and try again');
    }
    const isLocalHost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    const signalingUrls = resolveSignalingUrls(cfg);
    if (!signalingUrls.length) {
      throw new Error('Live signaling URL is missing');
    }
    if (!isLocalHost && signalingUrls.every((url) => /localhost|127\.0\.0\.1/i.test(url))) {
      throw new Error('Live signaling is not configured for this site');
    }

    onStatus?.('Waking live video server…');
    await Promise.allSettled(signalingUrls.map((url) => wakeLiveServer(url)));

    onStatus?.('Loading live player…');
    resetLiveLibs();
    const { io } = await loadSocketIo();

    let lastErr = null;
    for (const signalingUrl of signalingUrls) {
      onStatus?.('Connecting to live stream…');
      const socketOpts = socketIoOptions(signalingUrl);
      const connectTimeout = socketOpts.timeout;
      let socket = null;
      try {
        socket = io(signalingUrl, socketOpts);

        await new Promise((resolve, reject) => {
          const timer = setTimeout(
            () => reject(new Error('Live signaling timeout — try again in a moment')),
            connectTimeout,
          );
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
          liveId: cfg.live_id,
          token: cfg.token,
          role,
          userId: userId || 0,
        }, 25000);

        return { socket, join, rtc: cfg };
      } catch (err) {
        lastErr = err;
        try { socket?.disconnect(); } catch { /* ignore */ }
        console.warn('Live signaling failed (' + signalingUrl + '):', err?.message || err);
      }
    }

    throw lastErr || new Error('Live signaling connection failed');
  }

  async function connectSocket(rtc, role, userId, onStatus) {
    const attempts = [
      () => connectSocketAttempt(rtc, role, userId, onStatus),
      async () => {
        onStatus?.('Refreshing live connection…');
        await prepareForLiveConnect();
        return connectSocketAttempt(rtc, role, userId, onStatus);
      },
      async () => {
        onStatus?.('Resetting live cache…');
        await prepareForLiveConnect();
        if (window.__DL_purgePwaState) await window.__DL_purgePwaState();
        return connectSocketAttempt(rtc, role, userId, onStatus);
      },
    ];

    let lastErr = null;
    for (let i = 0; i < attempts.length; i++) {
      try {
        return await attempts[i]();
      } catch (err) {
        lastErr = err;
        if (i < attempts.length - 1) {
          await new Promise((r) => window.setTimeout(r, 600 * (i + 1)));
        }
      }
    }
    throw new Error(humanizeSocketError(lastErr));
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
      }).then(() => callback()).catch(errback);
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
      }).then(() => callback()).catch(errback);
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

  async function consumeAll(socket, device, transport, videoEl, seedProducers = []) {
    const remoteStream = new MediaStream();
    const consumedProducerIds = new Set();
    const items = seedProducers.length
      ? seedProducers
      : ((await emitAck(socket, 'live:getProducers')).items || []);
    const consumeErrors = [];
    let consumeAttempted = false;

    for (const item of items) {
      if (!item?.producerId || consumedProducerIds.has(item.producerId)) continue;
      consumeAttempted = true;
      try {
        await consumeProducer(socket, device, transport, item.producerId, remoteStream);
        consumedProducerIds.add(item.producerId);
      } catch (err) {
        consumeErrors.push(err.message || String(err));
        console.warn('Consume producer failed:', item.producerId, err.message);
      }
    }

    if (consumeAttempted) {
      await waitForTransportConnection(transport, 25000).catch((err) => {
        consumeErrors.push(err.message || String(err));
        console.warn('Transport connect:', err.message);
      });
    }

    if (consumeAttempted && !remoteStream.getTracks().length && consumeErrors.length) {
      throw new Error(consumeErrors[0] || 'Could not receive live video');
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
    const published = await produceTracks(sendTransport, stream);
    if (!published.length) {
      throw new Error('Camera or microphone not ready — allow permissions and try again');
    }
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
    live,
  }) {
    await stopWatching();

    const rtcConfig = normalizeRtcConfig(rtc, live);
    if (!rtcConfig.signaling_url) {
      throw new Error('Live RTC config missing');
    }

    onStatus?.('Connecting to live stream…');
    const [{ Device }, { socket, join }] = await Promise.all([
      loadMediasoupClient(),
      connectSocket(rtcConfig, 'viewer', userId, onStatus),
    ]);
    onConnected?.();

    onStatus?.('Starting video…');
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

    const callbacks = { onStreamReady, onWaiting };
    const seedProducers = Array.isArray(join.producers) ? join.producers : [];

    onStatus?.('Loading live video…');
    const consumed = await consumeAll(socket, device, recvTransport, videoEl, seedProducers);
    session.remoteStream = consumed.remoteStream;
    consumed.consumedProducerIds.forEach((id) => session.consumedProducerIds.add(id));

    if (!session.remoteStream.getTracks().length) {
      if (join.hasHost === false) {
        onWaiting?.('Waiting for host to connect…');
      } else {
        onWaiting?.('Waiting for host camera…');
      }
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
        await waitForTransportConnection(recvTransport, 20000).catch(() => {});
        if (videoEl) {
          await attachRemoteStreamToVideo(videoEl, session.remoteStream);
        }
        onStatus?.('');
        onStreamReady?.(session.remoteStream);
        clearProducerPoll();
      } catch (err) {
        console.warn('Consume new producer failed:', err.message);
        onWaiting?.('Receiving host video…');
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
    normalizeRtcConfig,
    prepareForLiveConnect,
    resetLiveLibs,
    isBroadcasting: () => Boolean(broadcastSession),
    isWatching: () => Boolean(watchSession),
  };
}
