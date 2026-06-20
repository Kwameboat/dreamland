#!/usr/bin/env node
/**
 * Writes web/env-config.js from Vercel env vars at build time.
 * Falls back to scripts/production-urls.json when env vars are missing.
 */
const fs = require('fs');
const path = require('path');

const defaults = JSON.parse(
  fs.readFileSync(path.join(__dirname, 'production-urls.json'), 'utf8')
);

function cleanUrl(value) {
  return String(value || '').replace(/\/$/, '');
}

const rootDomain = cleanUrl(
  process.env.DREAMLAND_ROOT_DOMAIN
  || defaults.rootDomain
  || 'dreamland.app'
).replace(/^https?:\/\//, '');

const apiUrl = cleanUrl(
  process.env.DREAMLAND_API_URL
  || process.env.VITE_DREAMLAND_API_URL
  || defaults.api
  || `https://api.${rootDomain}/v1`
);
const uploadsUrl = cleanUrl(process.env.DREAMLAND_UPLOADS_URL || defaults.uploads);
const pwaUrl = cleanUrl(
  process.env.DREAMLAND_PWA_URL
  || `https://${rootDomain}`
  || (process.env.VERCEL_PROJECT_PRODUCTION_URL
    ? `https://${process.env.VERCEL_PROJECT_PRODUCTION_URL}`
    : '')
  || (process.env.VERCEL_URL ? `https://${process.env.VERCEL_URL}` : '')
  || defaults.pwa
);

const payload = {
  rootDomain,
  api: apiUrl || null,
  uploads: uploadsUrl || null,
  pwa: pwaUrl || null,
  buildVersion: (
    process.env.VERCEL_GIT_COMMIT_SHA
    || process.env.GITHUB_SHA
    || `local-${Date.now()}`
  ).toString().slice(0, 12),
};

const outPath = path.join(__dirname, '..', 'web', 'env-config.js');
const contents = `/* Generated at build - do not edit */\nwindow.__DL_ENV__ = ${JSON.stringify(payload, null, 2)};\n`;

fs.writeFileSync(outPath, contents, 'utf8');
console.log('Wrote', outPath, payload);
