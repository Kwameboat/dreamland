const defaultCors = 'http://localhost:3000,http://127.0.0.1:3000,http://localhost:8080,https://dreamlandgh.app';

function parseRenderHostname() {
  const external = process.env.RENDER_EXTERNAL_URL || '';
  if (!external) return undefined;
  try {
    return new URL(external).hostname;
  } catch {
    return undefined;
  }
}

const isRender = Boolean(process.env.RENDER || process.env.RENDER_SERVICE_ID);
const renderHost = parseRenderHostname();

module.exports = {
  port: Number(process.env.PORT || process.env.DREAMLAND_LIVE_PORT || 4443),
  internalSecret: process.env.DREAMLAND_LIVE_SECRET || 'dreamland-live-dev-secret',
  corsOrigins: (process.env.DREAMLAND_LIVE_CORS || defaultCors)
    .split(',')
    .map((s) => s.trim())
    .filter(Boolean),
  deployTarget: process.env.DREAMLAND_LIVE_DEPLOY || (isRender ? 'render' : 'local'),
  mediasoup: {
    listenIp: process.env.DREAMLAND_LIVE_LISTEN_IP || '0.0.0.0',
    announcedIp: process.env.DREAMLAND_LIVE_ANNOUNCED_IP || renderHost || undefined,
    rtcMinPort: Number(process.env.DREAMLAND_LIVE_RTC_MIN || 40000),
    rtcMaxPort: Number(process.env.DREAMLAND_LIVE_RTC_MAX || 49999),
  },
  iceServers: [{ urls: 'stun:stun.l.google.com:19302' }],
};
