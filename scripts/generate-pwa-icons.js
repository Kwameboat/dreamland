#!/usr/bin/env node
/**
 * Build PWA install icons from web/assets/logo.png (official Dreamland logo).
 * Requires: npm install (sharp is a devDependency).
 */
const fs = require('fs');
const path = require('path');

let sharp;
try {
  sharp = require('sharp');
} catch {
  console.warn('sharp not installed — run: npm install');
  process.exit(0);
}

const logoPng = path.join(__dirname, '..', 'web', 'assets', 'logo.png');
const iconsDir = path.join(__dirname, '..', 'web', 'icons');

const sizes = [
  { name: 'icon-192.png', size: 192 },
  { name: 'icon-512.png', size: 512 },
  { name: 'apple-touch-icon.png', size: 180 },
  { name: 'icon-512-maskable.png', size: 512, maskable: true },
];

async function renderSquareIcon(input, size, maskable = false) {
  const inner = maskable ? Math.round(size * 0.82) : size;
  let pipeline = sharp(input).resize(inner, inner, {
    fit: 'contain',
    background: '#000000',
  });
  if (maskable) {
    const pad = Math.round((size - inner) / 2);
    pipeline = pipeline.extend({
      top: pad,
      bottom: size - inner - pad,
      left: pad,
      right: size - inner - pad,
      background: '#000000',
    });
  }
  return pipeline.png({ compressionLevel: 9 });
}

async function main() {
  if (!fs.existsSync(logoPng)) {
    console.error('Missing official logo:', logoPng);
    process.exit(1);
  }

  if (!fs.existsSync(iconsDir)) fs.mkdirSync(iconsDir, { recursive: true });

  const meta = await sharp(logoPng).metadata();
  console.log(`Source logo: ${meta.width}x${meta.height}`);

  for (const { name, size, maskable } of sizes) {
    const out = path.join(iconsDir, name);
    const pipeline = await renderSquareIcon(logoPng, size, maskable);
    await pipeline.toFile(out);
    console.log('Wrote', out);
  }

  const backendLogo = path.join(__dirname, '..', 'backend', 'sayhi_v1.6_code', 'backend', 'web', 'img', 'logo.png');
  const backendDir = path.dirname(backendLogo);
  if (!fs.existsSync(backendDir)) fs.mkdirSync(backendDir, { recursive: true });
  await fs.promises.copyFile(logoPng, backendLogo);
  console.log('Wrote', backendLogo);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
