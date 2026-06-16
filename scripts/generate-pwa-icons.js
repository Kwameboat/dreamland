#!/usr/bin/env node
/**
 * Rasterize web/assets/logo.svg into PNG icons for iOS/Android PWA install.
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

const logoSvg = path.join(__dirname, '..', 'web', 'assets', 'logo.svg');
const iconsDir = path.join(__dirname, '..', 'web', 'icons');
const svg = fs.readFileSync(logoSvg);

const sizes = [
  { name: 'icon-192.png', size: 192 },
  { name: 'icon-512.png', size: 512 },
  { name: 'apple-touch-icon.png', size: 180 },
  { name: 'icon-512-maskable.png', size: 512, maskable: true },
];

async function main() {
  if (!fs.existsSync(iconsDir)) fs.mkdirSync(iconsDir, { recursive: true });

  for (const { name, size, maskable } of sizes) {
    const out = path.join(iconsDir, name);
    let pipeline = sharp(svg).resize(size, size, { fit: 'contain', background: '#000000' });
    if (maskable) {
      pipeline = sharp(svg).resize(Math.round(size * 0.8), Math.round(size * 0.8), {
        fit: 'contain',
        background: '#000000',
      }).extend({
        top: Math.round(size * 0.1),
        bottom: Math.round(size * 0.1),
        left: Math.round(size * 0.1),
        right: Math.round(size * 0.1),
        background: '#000000',
      });
    }
    await pipeline.png({ compressionLevel: 9 }).toFile(out);
    console.log('Wrote', out);
  }

  const logoPng = path.join(__dirname, '..', 'web', 'assets', 'logo.png');
  await sharp(svg).resize(512, 512, { fit: 'contain', background: '#000000' })
    .png({ compressionLevel: 9 })
    .toFile(logoPng);
  console.log('Wrote', logoPng);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
