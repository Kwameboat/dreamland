#!/usr/bin/env node
/**
 * Upload production fix files to cPanel via SFTP and run apply-live-fix.sh
 * Usage: $env:CPANEL_SSH_PASSWORD='your-password'; node scripts/upload-live-fix.js
 */
const fs = require('fs');
const path = require('path');
const { Client } = require('ssh2');

const root = path.join(__dirname, '..');
const backend = path.join(root, 'backend', 'sayhi_v1.6_code');
const credPath = path.join(root, 'deploy', 'cpanel', 'ftp-credentials.local.json');

function loadCred() {
  const file = JSON.parse(fs.readFileSync(credPath, 'utf8'));
  const password = process.env.CPANEL_SSH_PASSWORD || file.password;
  if (!password || password === 'REPLACE_ME') {
    throw new Error('Set CPANEL_SSH_PASSWORD env var');
  }
  return {
    host: file.host || 'server360.web-hosting.com',
    port: Number(process.env.CPANEL_SSH_PORT || 21098),
    username: file.user || 'dreaxdjo',
    password,
    readyTimeout: 60000,
  };
}

function sftpPut(sftp, local, remote) {
  return new Promise((resolve, reject) => {
    const read = fs.createReadStream(local);
    const write = sftp.createWriteStream(remote);
    write.on('close', resolve);
    write.on('error', reject);
    read.on('error', reject);
    read.pipe(write);
  });
}

function exec(conn, cmd) {
  return new Promise((resolve, reject) => {
    conn.exec(cmd, (err, stream) => {
      if (err) return reject(err);
      let out = '';
      stream.on('close', (code) => (code ? reject(new Error(out || cmd)) : resolve(out)));
      stream.on('data', (d) => {
        out += d;
        process.stdout.write(d);
      });
      stream.stderr.on('data', (d) => process.stderr.write(d));
    });
  });
}

async function main() {
  const config = loadCred();
  const home = `/home/${config.username}`;
  const uploads = [
    [path.join(backend, 'backend/views/layouts/main-login.php'), `${home}/dreamland/backend/views/layouts/main-login.php`],
    [path.join(backend, 'backend/views/layouts/content.php'), `${home}/dreamland/backend/views/layouts/content.php`],
    [path.join(backend, 'backend/views/layouts/purchase-code.php'), `${home}/dreamland/backend/views/layouts/purchase-code.php`],
    [path.join(backend, 'api/modules/v1/controllers/HealthController.php'), `${home}/dreamland/api/modules/v1/controllers/HealthController.php`],
    [path.join(backend, 'common/helpers/DreamlandWasabiStorage.php'), `${home}/dreamland/common/helpers/DreamlandWasabiStorage.php`],
    [path.join(root, 'deploy/cpanel/config/backend-subdir.php'), `${home}/dreamland/deploy/cpanel/config/backend-subdir.php`],
    [path.join(root, 'deploy/cpanel/entrypoints/admin-index.php'), `${home}/public_html/admin/index.php`],
    [path.join(root, 'deploy/cpanel/entrypoints/api-boot-test.php'), `${home}/public_html/api/boot-test.php`],
    [path.join(root, 'deploy/cpanel/apply-live-fix.sh'), `${home}/dreamland/deploy/cpanel/apply-live-fix.sh`],
  ];

  const conn = new Client();
  await new Promise((resolve, reject) => {
    conn.on('ready', resolve);
    conn.on('error', reject);
    conn.connect(config);
  });

  await new Promise((resolve, reject) => {
    conn.sftp(async (err, sftp) => {
      if (err) return reject(err);
      try {
        for (const [local, remote] of uploads) {
          if (!fs.existsSync(local)) {
            console.warn('Skip missing:', local);
            continue;
          }
          console.log('Upload', path.basename(local));
          await sftpPut(sftp, local, remote);
        }
        resolve();
      } catch (e) {
        reject(e);
      }
    });
  });

  console.log('\nRunning apply-live-fix.sh ...');
  await exec(conn, `chmod +x ${home}/dreamland/deploy/cpanel/apply-live-fix.sh && bash ${home}/dreamland/deploy/cpanel/apply-live-fix.sh`);
  conn.end();
}

main().catch((e) => {
  console.error('\nUpload failed:', e.message);
  process.exit(1);
});
