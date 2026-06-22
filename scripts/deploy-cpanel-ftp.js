#!/usr/bin/env node
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const root = path.join(__dirname, '..');
const credPath = path.join(root, 'deploy', 'cpanel', 'ftp-credentials.local.json');
const zipPath = path.join(root, 'dist', 'dreamland-cpanel.zip');

if (!fs.existsSync(credPath)) {
  console.error('Missing', credPath);
  process.exit(1);
}
if (!fs.existsSync(zipPath)) {
  console.error('Run: npm run build:cpanel');
  process.exit(1);
}

const cred = JSON.parse(fs.readFileSync(credPath, 'utf8'));
const user = encodeURIComponent(cred.user);
const pass = encodeURIComponent(cred.password);
const host = cred.host;
const url = `ftp://${host}/dreamland-cpanel.zip`;

console.log('Uploading', zipPath, 'to', host, '...');
execSync(`curl --ftp-create-dirs -T "${zipPath}" "${url}" --user "${user}:${pass}"`, {
  stdio: 'inherit',
  shell: true,
});
console.log('Upload complete. Extract in cPanel File Manager at /home/' + cred.user + '/');
