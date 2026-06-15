#!/usr/bin/env node
/**
 * Writes web/env-config.js from Vercel env vars at build time.
 * Set DREAMLAND_API_URL and optionally DREAMLAND_UPLOADS_URL in Vercel project settings.
 */
const fs = require('fs');
const path = require('path');

const apiUrl = (process.env.DREAMLAND_API_URL || process.env.VITE_DREAMLAND_API_URL || '').replace(/\/$/, '');
const uploadsUrl = (process.env.DREAMLAND_UPLOADS_URL || '').replace(/\/$/, '');

const payload = {
  api: apiUrl || null,
  uploads: uploadsUrl || null,
};

const outPath = path.join(__dirname, '..', 'web', 'env-config.js');
const contents = `/* Generated at build - do not edit */\nwindow.__DL_ENV__ = ${JSON.stringify(payload, null, 2)};\n`;

fs.writeFileSync(outPath, contents, 'utf8');
console.log('Wrote', outPath, payload);
