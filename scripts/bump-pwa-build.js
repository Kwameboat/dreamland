#!/usr/bin/env node
/**
 * Bumps PWA cache + build version on each deploy so clients fetch fresh assets.
 */
const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');
const web = path.join(root, 'web');
const version = (
  process.env.VERCEL_GIT_COMMIT_SHA
  || process.env.GITHUB_SHA
  || `build-${Date.now()}`
).toString().slice(0, 12);

const builtAt = new Date().toISOString();
const versionPayload = {
  version,
  builtAt,
};
fs.writeFileSync(path.join(web, 'build-version.json'), `${JSON.stringify(versionPayload, null, 2)}\n`, 'utf8');

const envPath = path.join(web, 'env-config.js');
if (fs.existsSync(envPath)) {
  let envJs = fs.readFileSync(envPath, 'utf8');
  const match = envJs.match(/window\.__DL_ENV__\s*=\s*(\{[\s\S]*\});?/);
  if (match) {
    const payload = JSON.parse(match[1]);
    payload.buildVersion = version;
    envJs = `/* Generated at build - do not edit */\nwindow.__DL_ENV__ = ${JSON.stringify(payload, null, 2)};\n`;
    fs.writeFileSync(envPath, envJs, 'utf8');
  }
}

const embedPayload = JSON.stringify({ version, builtAt });
const indexPath = path.join(web, 'index.html');
if (fs.existsSync(indexPath)) {
  let html = fs.readFileSync(indexPath, 'utf8');
  html = html.replace(
    /window\.__DL_BUILD_EMBED__\s*=\s*\{[^}]+\};/,
    `window.__DL_BUILD_EMBED__=${embedPayload};`,
  );
  html = html.replace(/\/js\/app\.js\?v=[^'"]+/, `/js/app.js?v=${version}`);
  fs.writeFileSync(indexPath, html, 'utf8');
}

console.log('PWA build version:', version);
