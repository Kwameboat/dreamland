#!/usr/bin/env node
/**
 * Hot-deploy PWA + paywall fixes to cPanel via SFTP.
 * Usage: node scripts/deploy-pwa-hotfix.js
 */
const fs = require('fs');
const path = require('path');
const { Client } = require('ssh2');

const root = path.join(__dirname, '..');
const backend = path.join(root, 'backend', 'sayhi_v1.6_code');
const web = path.join(root, 'web');
const credPath = path.join(root, 'deploy', 'cpanel', 'ftp-credentials.local.json');

function loadCred() {
  if (!fs.existsSync(credPath)) {
    throw new Error(`Missing ${credPath}`);
  }
  const file = JSON.parse(fs.readFileSync(credPath, 'utf8'));
  const password = process.env.CPANEL_SSH_PASSWORD || file.password;
  if (!password || password === 'REPLACE_ME' || password === 'YOUR_CPANEL_PASSWORD') {
    throw new Error('Set CPANEL_SSH_PASSWORD or password in ftp-credentials.local.json');
  }
  return {
    host: file.host || 'server360.web-hosting.com',
    port: Number(process.env.CPANEL_SSH_PORT || 21098),
    username: file.user || 'dreaxdjo',
    password,
    readyTimeout: 90000,
  };
}

function sftpPut(sftp, local, remote) {
  return new Promise((resolve, reject) => {
    if (!fs.existsSync(local)) {
      reject(new Error(`Missing local file: ${local}`));
      return;
    }
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
      let errOut = '';
      stream.on('close', (code) => {
        if (code !== 0) {
          reject(new Error(`Command failed (${code}): ${errOut || out || cmd}`));
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

async function main() {
  const config = loadCred();
  const home = `/home/${config.username}`;
  const uploads = [
    [path.join(backend, 'api/modules/v1/controllers/PostController.php'), `${home}/dreamland/api/modules/v1/controllers/PostController.php`],
    [path.join(web, 'js/app.js'), `${home}/public_html/js/app.js`],
    [path.join(web, 'js/config.js'), `${home}/public_html/js/config.js`],
    [path.join(web, 'js/dreamland-features.js'), `${home}/public_html/js/dreamland-features.js`],
    [path.join(web, 'js/dreamland-ai.js'), `${home}/public_html/js/dreamland-ai.js`],
    [path.join(web, 'js/dreamland-social.js'), `${home}/public_html/js/dreamland-social.js`],
    [path.join(web, 'js/dreamland-profile.js'), `${home}/public_html/js/dreamland-profile.js`],
    [path.join(web, 'js/dreamland-search.js'), `${home}/public_html/js/dreamland-search.js`],
    [path.join(web, 'js/dreamland-account.js'), `${home}/public_html/js/dreamland-account.js`],
    [path.join(web, 'js/dreamland-reels-fast.js'), `${home}/public_html/js/dreamland-reels-fast.js`],
    [path.join(web, 'js/dreamland-live.js'), `${home}/public_html/js/dreamland-live.js`],
    [path.join(web, 'index.html'), `${home}/public_html/index.html`],
    [path.join(web, 'build-version.json'), `${home}/public_html/build-version.json`],
    [path.join(web, 'env-config.js'), `${home}/public_html/env-config.js`],
    [path.join(web, 'sw.js'), `${home}/public_html/sw.js`],
    [path.join(web, '.htaccess'), `${home}/public_html/.htaccess`],
  ];

  const conn = new Client();
  console.log(`Connecting to ${config.username}@${config.host}:${config.port}...`);
  await new Promise((resolve, reject) => {
    conn.on('ready', resolve);
    conn.on('error', reject);
    conn.connect(config);
  });
  console.log('Connected.\n');

  await new Promise((resolve, reject) => {
    conn.sftp(async (err, sftp) => {
      if (err) return reject(err);
      try {
        for (const [local, remote] of uploads) {
          console.log(`Upload ${path.basename(local)}`);
          await sftpPut(sftp, local, remote);
        }
        resolve();
      } catch (e) {
        reject(e);
      }
    });
  });

  console.log('\nClearing API cache...');
  await exec(conn, `rm -rf ${home}/dreamland/api/runtime/cache/* 2>/dev/null || true`);

  console.log('\nVerifying live build...');
  await exec(conn, `grep -o 'build-[0-9]*' ${home}/public_html/build-version.json | head -1`);
  await exec(conn, `test -f ${home}/public_html/js/dreamland-reels-fast.js && head -c 40 ${home}/public_html/js/dreamland-reels-fast.js`);

  conn.end();
  console.log('\nDeploy finished. Hard refresh: https://dreamlandgh.app (Ctrl+Shift+R)');
}

main().catch((e) => {
  console.error('\nDeploy failed:', e.message);
  process.exit(1);
});
