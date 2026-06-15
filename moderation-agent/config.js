require('./lib/load-env').loadEnv();

module.exports = {
  port: Number(process.env.DREAMLAND_MOD_PORT || 4444),
  internalSecret: process.env.DREAMLAND_MOD_SECRET || 'dreamland-mod-dev-secret',
  corsOrigins: (process.env.DREAMLAND_MOD_CORS || 'http://localhost:8081,http://localhost:3000,http://127.0.0.1:8081')
    .split(',')
    .map((s) => s.trim())
    .filter(Boolean),
  blockThreshold: Number(process.env.DREAMLAND_MOD_BLOCK_SCORE || 70),
  reviewThreshold: Number(process.env.DREAMLAND_MOD_REVIEW_SCORE || 40),
  useOpenAi: process.env.DREAMLAND_MOD_USE_OPENAI === '1',
  useGemini: process.env.DREAMLAND_USE_GEMINI !== '0',
  geminiApiKey: process.env.GEMINI_API_KEY || process.env.GOOGLE_API_KEY || '',
  geminiModel: process.env.DREAMLAND_GEMINI_MODEL || 'gemini-2.0-flash',
  pollMs: Number(process.env.DREAMLAND_MOD_POLL_MS || 4000),
  db: {
    host: process.env.DB_HOST || '127.0.0.1',
    port: Number(process.env.DB_PORT || 3309),
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || 'root',
    database: process.env.DB_NAME || 'yii2advanced',
  },
};
