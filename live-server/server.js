'use strict';

const express = require('express');
const http = require('http');
const cors = require('cors');
const mediasoup = require('mediasoup');
const { Server } = require('socket.io');
const config = require('./config');

const mediaCodecs = [
  {
    kind: 'audio',
    mimeType: 'audio/opus',
    clockRate: 48000,
    channels: 2,
  },
  {
    kind: 'video',
    mimeType: 'video/VP8',
    clockRate: 90000,
    parameters: { 'x-google-start-bitrate': 1000 },
  },
  {
    kind: 'video',
    mimeType: 'video/h264',
    clockRate: 90000,
    parameters: {
      'packetization-mode': 1,
      'profile-level-id': '42e01f',
      'level-asymmetry-allowed': 1,
      'x-google-start-bitrate': 1000,
    },
  },
];

/** @type {import('mediasoup').types.Worker} */
let worker;
/** @type {Map<number, LiveRoom>} */
const rooms = new Map();
/** @type {Map<string, { liveId: number, role: string, userId: number }>} */
const socketMeta = new Map();

class LiveRoom {
  constructor(liveId, hostUserId) {
    this.liveId = liveId;
    this.hostUserId = hostUserId;
    /** @type {import('mediasoup').types.Router | null} */
    this.router = null;
    this.accessToken = '';
    this.hostSocketId = null;
    this.viewers = new Set();
    this.producers = new Map();
    this.transports = new Map();
    this.consumers = new Map();
    this.chat = [];
  }

  viewerCount() {
    return this.viewers.size + (this.hostSocketId ? 1 : 0);
  }
}

async function createWorker() {
  worker = await mediasoup.createWorker({
    rtcMinPort: config.mediasoup.rtcMinPort,
    rtcMaxPort: config.mediasoup.rtcMaxPort,
    logLevel: 'warn',
  });
  worker.on('died', () => {
    console.error('Mediasoup worker died — exiting');
    process.exit(1);
  });
  console.log('Mediasoup worker started');
}

function listenOptions() {
  const listenIp = config.mediasoup.listenIp;
  const announcedIp = config.mediasoup.announcedIp;
  if (announcedIp) {
    return [{ ip: listenIp, announcedIp }];
  }
  return [{ ip: listenIp }];
}

async function getOrCreateRoom(liveId, hostUserId) {
  let room = rooms.get(liveId);
  if (!room) {
    room = new LiveRoom(liveId, hostUserId || 0);
    room.router = await worker.createRouter({ mediaCodecs });
    rooms.set(liveId, room);
    console.log('Room created', liveId);
  }
  return room;
}

function requireRoom(liveId) {
  const room = rooms.get(Number(liveId));
  if (!room || !room.router) {
    const err = new Error('Live room not found or ended');
    err.code = 'ROOM_NOT_FOUND';
    throw err;
  }
  return room;
}

async function closeRoom(liveId) {
  const room = rooms.get(Number(liveId));
  if (!room) return;
  for (const transport of room.transports.values()) {
    try {
      transport.close();
    } catch (_) {
      /* noop */
    }
  }
  room.router?.close();
  rooms.delete(Number(liveId));
  console.log('Room closed', liveId);
}

async function boot() {
  await createWorker();

  const app = express();
  app.use(cors({ origin: config.corsOrigins, credentials: true }));
  app.use(express.json());

  app.get('/health', (_req, res) => {
    res.json({
      ok: true,
      service: 'dreamland-live',
      rooms: rooms.size,
      uptime: process.uptime(),
    });
  });

  app.get('/api/config', (_req, res) => {
    res.json({
      iceServers: config.iceServers,
      port: config.port,
    });
  });

  app.post('/internal/rooms', (req, res) => {
    if (req.headers['x-dreamland-secret'] !== config.internalSecret) {
      return res.status(403).json({ ok: false, message: 'Forbidden' });
    }
    const liveId = Number(req.body.liveId || 0);
    const hostUserId = Number(req.body.hostUserId || 0);
    const token = String(req.body.token || '');
    if (!liveId || !token) {
      return res.status(422).json({ ok: false, message: 'liveId and token required' });
    }
    getOrCreateRoom(liveId, hostUserId)
      .then((room) => {
        room.accessToken = token;
        room.hostUserId = hostUserId;
        res.json({ ok: true, liveId, viewerCount: room.viewerCount() });
      })
      .catch((err) => res.status(500).json({ ok: false, message: err.message }));
  });

  app.delete('/internal/rooms/:liveId', async (req, res) => {
    if (req.headers['x-dreamland-secret'] !== config.internalSecret) {
      return res.status(403).json({ ok: false, message: 'Forbidden' });
    }
    const liveId = Number(req.params.liveId);
    await closeRoom(liveId);
    res.json({ ok: true, liveId });
  });

  app.get('/internal/rooms/:liveId/status', (req, res) => {
    if (req.headers['x-dreamland-secret'] !== config.internalSecret) {
      return res.status(403).json({ ok: false, message: 'Forbidden' });
    }
    const room = rooms.get(Number(req.params.liveId));
    res.json({
      ok: true,
      active: Boolean(room),
      viewerCount: room ? room.viewerCount() : 0,
      hasHost: Boolean(room?.hostSocketId),
      producers: room ? room.producers.size : 0,
    });
  });

  const server = http.createServer(app);
  const io = new Server(server, {
    cors: { origin: config.corsOrigins, credentials: true },
    maxHttpBufferSize: 1e7,
  });

  io.on('connection', (socket) => {
    socket.on('live:join', async (payload, ack) => {
      try {
        const liveId = Number(payload?.liveId || 0);
        const token = String(payload?.token || '');
        const role = String(payload?.role || 'viewer');
        const userId = Number(payload?.userId || 0);

        const room = requireRoom(liveId);
        if (room.accessToken !== token) {
          throw new Error('Invalid live token');
        }

        socketMeta.set(socket.id, { liveId, role, userId });
        socket.join(`live:${liveId}`);

        if (role === 'host') {
          room.hostSocketId = socket.id;
        } else {
          room.viewers.add(socket.id);
        }

        io.to(`live:${liveId}`).emit('live:stats', {
          liveId,
          viewerCount: room.viewerCount(),
        });

        if (typeof ack === 'function') {
          ack({
            ok: true,
            rtpCapabilities: room.router.rtpCapabilities,
            iceServers: config.iceServers,
            viewerCount: room.viewerCount(),
            chat: room.chat.slice(-30),
          });
        }
      } catch (err) {
        if (typeof ack === 'function') ack({ ok: false, message: err.message });
      }
    });

    socket.on('live:createTransport', async (payload, ack) => {
      try {
        const meta = socketMeta.get(socket.id);
        if (!meta) throw new Error('Not joined');
        const room = requireRoom(meta.liveId);
        const direction = payload?.direction === 'recv' ? 'recv' : 'send';

        const transport = await room.router.createWebRtcTransport({
          listenIps: listenOptions(),
          enableUdp: true,
          enableTcp: true,
          preferUdp: true,
          appData: { socketId: socket.id, direction },
        });

        room.transports.set(transport.id, transport);
        transport.on('dtlsstatechange', (state) => {
          if (state === 'closed') transport.close();
        });

        ack({
          ok: true,
          id: transport.id,
          iceParameters: transport.iceParameters,
          iceCandidates: transport.iceCandidates,
          dtlsParameters: transport.dtlsParameters,
        });
      } catch (err) {
        ack({ ok: false, message: err.message });
      }
    });

    socket.on('live:connectTransport', async (payload, ack) => {
      try {
        const meta = socketMeta.get(socket.id);
        const room = requireRoom(meta.liveId);
        const transport = room.transports.get(payload?.transportId);
        if (!transport) throw new Error('Transport not found');
        await transport.connect({ dtlsParameters: payload.dtlsParameters });
        ack({ ok: true });
      } catch (err) {
        ack({ ok: false, message: err.message });
      }
    });

    socket.on('live:produce', async (payload, ack) => {
      try {
        const meta = socketMeta.get(socket.id);
        const room = requireRoom(meta.liveId);
        const transport = room.transports.get(payload?.transportId);
        if (!transport) throw new Error('Transport not found');

        const producer = await transport.produce({
          kind: payload.kind,
          rtpParameters: payload.rtpParameters,
          appData: { socketId: socket.id },
        });

        room.producers.set(producer.id, producer);
        producer.on('transportclose', () => room.producers.delete(producer.id));

        socket.to(`live:${meta.liveId}`).emit('live:newProducer', {
          producerId: producer.id,
          kind: producer.kind,
        });

        ack({ ok: true, id: producer.id });
      } catch (err) {
        ack({ ok: false, message: err.message });
      }
    });

    socket.on('live:getProducers', (_payload, ack) => {
      try {
        const meta = socketMeta.get(socket.id);
        const room = requireRoom(meta.liveId);
        const items = [];
        room.producers.forEach((producer, id) => {
          items.push({ producerId: id, kind: producer.kind });
        });
        ack({ ok: true, items });
      } catch (err) {
        ack({ ok: false, message: err.message });
      }
    });

    socket.on('live:consume', async (payload, ack) => {
      try {
        const meta = socketMeta.get(socket.id);
        const room = requireRoom(meta.liveId);
        const producer = room.producers.get(payload?.producerId);
        if (!producer) throw new Error('Producer not found');

        if (!room.router.canConsume({
          producerId: producer.id,
          rtpCapabilities: payload.rtpCapabilities,
        })) {
          throw new Error('Cannot consume');
        }

        const transport = room.transports.get(payload?.transportId);
        if (!transport) throw new Error('Transport not found');

        const consumer = await transport.consume({
          producerId: producer.id,
          rtpCapabilities: payload.rtpCapabilities,
          paused: true,
        });

        room.consumers.set(consumer.id, consumer);
        consumer.on('transportclose', () => room.consumers.delete(consumer.id));

        ack({
          ok: true,
          id: consumer.id,
          producerId: producer.id,
          kind: consumer.kind,
          rtpParameters: consumer.rtpParameters,
        });
      } catch (err) {
        ack({ ok: false, message: err.message });
      }
    });

    socket.on('live:resumeConsumer', async (payload, ack) => {
      try {
        const meta = socketMeta.get(socket.id);
        const room = requireRoom(meta.liveId);
        const consumer = room.consumers.get(payload?.consumerId);
        if (!consumer) throw new Error('Consumer not found');
        await consumer.resume();
        ack({ ok: true });
      } catch (err) {
        ack({ ok: false, message: err.message });
      }
    });

    socket.on('live:chat', (payload) => {
      const meta = socketMeta.get(socket.id);
      if (!meta) return;
      const room = rooms.get(meta.liveId);
      if (!room) return;
      const message = {
        userId: meta.userId,
        role: meta.role,
        text: String(payload?.text || '').slice(0, 280),
        at: Date.now(),
      };
      if (!message.text) return;
      room.chat.push(message);
      if (room.chat.length > 100) room.chat.shift();
      io.to(`live:${meta.liveId}`).emit('live:chat', message);
    });

    socket.on('disconnect', () => {
      const meta = socketMeta.get(socket.id);
      if (!meta) return;
      const room = rooms.get(meta.liveId);
      if (room) {
        if (room.hostSocketId === socket.id) room.hostSocketId = null;
        room.viewers.delete(socket.id);
        io.to(`live:${meta.liveId}`).emit('live:stats', {
          liveId: meta.liveId,
          viewerCount: room.viewerCount(),
        });
      }
      socketMeta.delete(socket.id);
    });
  });

  server.listen(config.port, () => {
    console.log(`Dreamland Live Server on http://localhost:${config.port}`);
    console.log('WebRTC SFU ready — no third-party streaming API required');
  });
}

boot().catch((err) => {
  console.error(err);
  process.exit(1);
});
