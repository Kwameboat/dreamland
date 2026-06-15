module.exports = {
  port: Number(process.env.DREAMLAND_LIVE_PORT || 4443),
  internalSecret: process.env.DREAMLAND_LIVE_SECRET || 'dreamland-live-dev-secret',
  corsOrigins: (process.env.DREAMLAND_LIVE_CORS || 'http://localhost:3000,http://127.0.0.1:3000,http://localhost:8080')
    .split(',')
    .map((s) => s.trim())
    .filter(Boolean),
  mediasoup: {
    listenIp: process.env.DREAMLAND_LIVE_LISTEN_IP || '0.0.0.0',
    announcedIp: process.env.DREAMLAND_LIVE_ANNOUNCED_IP || undefined,
    rtcMinPort: Number(process.env.DREAMLAND_LIVE_RTC_MIN || 40000),
    rtcMaxPort: Number(process.env.DREAMLAND_LIVE_RTC_MAX || 49999),
  },
  iceServers: [{ urls: 'stun:stun.l.google.com:19302' }],
};
