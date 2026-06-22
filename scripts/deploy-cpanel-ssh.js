#!/usr/bin/env node
/**
 * Deploy dreamland-cpanel.zip to Namecheap/cPanel via SSH + SFTP.
 *
 * Set password via env (never commit):
 *   $env:CPANEL_SSH_PASSWORD='your-password'
 *   node scripts/deploy-cpanel-ssh.js
 */
const fs = require('fs');
const path = require('path');
const { Client } = require('ssh2');

const root = path.join(__dirname, '..');
const credPath = path.join(root, 'deploy', 'cpanel', 'ftp-credentials.local.json');
const zipPath = path.join(root, 'dist', 'dreamland-cpanel.zip');
const remoteZip = 'dreamland-cpanel.zip';

function loadCred() {
  const file = JSON.parse(fs.readFileSync(credPath, 'utf8'));
  const password = process.env.CPANEL_SSH_PASSWORD || file.password;
  if (!password || password === 'REPLACE_ME') {
    throw new Error('Set CPANEL_SSH_PASSWORD env var or deploy/cpanel/ftp-credentials.local.json password');
  }
  return {
    host: file.host || 'server360.web-hosting.com',
    port: Number(process.env.CPANEL_SSH_PORT || 21098),
    username: file.user || 'dreaxdjo',
    password,
    readyTimeout: 60000,
  };
}

function exec(conn, cmd) {
  return new Promise((resolve, reject) => {
    conn.exec(cmd, (err, stream) => {
      if (err) return reject(err);
      let out = '';
      let errOut = '';
      stream.on('close', (code) => {
        if (code !== 0) {
          reject(new Error(`Command failed (${code}): ${cmd}\n${errOut || out}`));
        } else {
          resolve(out);
        }
      });
      stream.on('data', (d) => {
        out += d;
        process.stdout.write(d);
      });
      stream.stderr.on('data', (d) => {
        errOut += d;
        process.stderr.write(d);
      });
    });
  });
}

function sftpUpload(conn, local, remote) {
  return new Promise((resolve, reject) => {
    conn.sftp((err, sftp) => {
      if (err) return reject(err);
      const read = fs.createReadStream(local);
      const write = sftp.createWriteStream(remote);
      let transferred = 0;
      const total = fs.statSync(local).size;
      read.on('data', (chunk) => {
        transferred += chunk.length;
        const pct = Math.round((transferred / total) * 100);
        process.stdout.write(`\rUpload ${pct}%`);
      });
      write.on('close', () => {
        process.stdout.write('\n');
        resolve();
      });
      write.on('error', reject);
      read.on('error', reject);
      read.pipe(write);
    });
  });
}

async function main() {
  if (!fs.existsSync(zipPath)) {
    throw new Error('Missing zip — run: npm run build:cpanel');
  }

  const config = loadCred();
  const conn = new Client();
  const sizeMb = (fs.statSync(zipPath).size / (1024 * 1024)).toFixed(1);

  console.log(`Connecting to ${config.username}@${config.host}...`);
  await new Promise((resolve, reject) => {
    conn.on('ready', resolve);
    conn.on('error', reject);
    conn.connect(config);
  });
  console.log('Connected.');

  console.log(`Uploading ${zipPath} (${sizeMb} MB)...`);
  await sftpUpload(conn, zipPath, remoteZip);

  const home = `/home/${config.username}`;
  const script = `
set -e
cd ${home}
echo "=== Extract package ==="
rm -rf dreamland-cpanel-tmp
mkdir -p dreamland-cpanel-tmp
unzip -o -q ${remoteZip} -d dreamland-cpanel-tmp

echo "=== Install dreamland app ==="
rm -rf dreamland.bak
[ -d dreamland ] && mv dreamland dreamland.bak || true
mv dreamland-cpanel-tmp/dreamland dreamland

echo "=== Merge public_html ==="
shopt -s dotglob
for item in dreamland-cpanel-tmp/public_html/*; do
  name=$(basename "$item")
  if [ "$name" = "admin" ] || [ "$name" = "api" ]; then
    rm -rf public_html/$name
    cp -a "$item" public_html/$name
  else
    cp -a "$item" public_html/
  fi
done
rm -rf dreamland-cpanel-tmp

echo "=== Composer install ==="
cd ${home}/dreamland
if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --optimize-autoloader --no-interaction 2>&1
elif [ -f /opt/cpanel/composer/bin/composer ]; then
  /opt/cpanel/composer/bin/composer install --no-dev --optimize-autoloader --no-interaction 2>&1
else
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  php composer-setup.php --quiet
  php composer.phar install --no-dev --optimize-autoloader --no-interaction 2>&1
fi

echo "=== Permissions ==="
chmod -R 775 api/runtime backend/runtime common/runtime backend/web/assets 2>/dev/null || true
find api/runtime backend/runtime common/runtime -type d -exec chmod 775 {} \\; 2>/dev/null || true

echo "=== Done ==="
ls -la ${home}/public_html/admin/index.php
ls -la ${home}/dreamland/vendor/autoload.php 2>/dev/null || echo "WARN: vendor missing"
echo "PWA: https://dreamlandgh.app"
echo "Admin: https://dreamlandgh.app/admin/site/login"
echo "API health: https://dreamlandgh.app/api/v1/health"
`;

  console.log('Running remote setup...');
  await exec(conn, `bash -lc ${JSON.stringify(script)}`);
  conn.end();
  console.log('\nDeploy finished.');
}

main().catch((e) => {
  console.error('\nDeploy failed:', e.message);
  process.exit(1);
});
