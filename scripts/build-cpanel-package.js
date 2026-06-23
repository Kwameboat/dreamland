#!/usr/bin/env node
/**
 * Build dreamland-cpanel.zip for Namecheap/cPanel hosting.
 * Usage: node scripts/build-cpanel-package.js
 */
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const domain = process.env.DOMAIN || 'dreamlandgh.app';
const root = path.join(__dirname, '..');
const backend = path.join(root, 'backend', 'sayhi_v1.6_code');
const web = path.join(root, 'web');
const deploy = path.join(root, 'deploy', 'cpanel');
const dist = path.join(root, 'dist', 'cpanel-package');
const zipPath = path.join(root, 'dist', 'dreamland-cpanel.zip');

function rmrf(p) {
  if (fs.existsSync(p)) fs.rmSync(p, { recursive: true, force: true });
}

function mkdirp(p) {
  fs.mkdirSync(p, { recursive: true });
}

function copyRecursive(src, dest, skipDirs = []) {
  mkdirp(dest);
  for (const name of fs.readdirSync(src)) {
    if (skipDirs.includes(name)) continue;
    const s = path.join(src, name);
    const d = path.join(dest, name);
    const st = fs.statSync(s);
    if (st.isDirectory()) copyRecursive(s, d, skipDirs);
    else fs.copyFileSync(s, d);
  }
}

console.log('Dreamland cPanel build —', domain);

if (!process.env.SKIP_COMPOSER) {
  console.log('composer install...');
  execSync('composer install --no-dev --optimize-autoloader --no-interaction', {
    cwd: backend,
    stdio: 'inherit',
  });
}

console.log('PWA build...');
execSync('npm run build:web', {
  cwd: root,
  stdio: 'inherit',
  env: {
    ...process.env,
    DREAMLAND_ROOT_DOMAIN: domain,
    DREAMLAND_PWA_URL: `https://${domain}`,
    DREAMLAND_API_URL: `https://${domain}/api/v1`,
  },
});

rmrf(dist);
mkdirp(dist);
const yiiDest = path.join(dist, 'dreamland');
mkdirp(yiiDest);

console.log('Copying Yii app (includes vendor)...');
if (process.platform === 'win32') {
  mkdirp(yiiDest);
  try {
    execSync(
      `robocopy "${backend}" "${yiiDest}" /E /XD node_modules .git tests chat vendor runtime /NFL /NDL /NJH /NJS /nc /ns /np`,
      { stdio: 'inherit' }
    );
  } catch (e) {
    if (!e.status || e.status > 7) throw e;
  }
  // composer.json + lock only — run composer install on cPanel Terminal (avoids Windows long-path zip issues)
  console.log('Package excludes vendor/ — run composer install on server after upload.');
} else {
  copyRecursive(backend, yiiDest, ['node_modules', '.git', 'tests', 'chat', 'vendor', 'runtime']);
}

const deployDest = path.join(yiiDest, 'deploy', 'cpanel');
mkdirp(deployDest);
copyRecursive(path.join(deploy, 'config'), path.join(deployDest, 'config'));
fs.copyFileSync(path.join(deploy, 'env.template'), path.join(yiiDest, '.env.template'));

const copies = [
  ['common/config/main-local.example.php', 'common/config/main-local.php'],
  ['common/config/params-supabase.example.php', 'common/config/params-local.php'],
  ['backend/config/main-local.example.php', 'backend/config/main-local.php'],
  ['api/config/main-local.example.php', 'api/config/main-local.php'],
];
for (const [from, to] of copies) {
  fs.copyFileSync(path.join(backend, from), path.join(yiiDest, to));
}

for (const sub of [
  'api/runtime', 'api/runtime/uploads/user', 'api/runtime/uploads/image',
  'backend/runtime', 'common/runtime', 'backend/web/assets',
]) {
  mkdirp(path.join(yiiDest, sub));
}

const envOut = path.join(yiiDest, '.env');
const supabaseEnv = path.join(root, '.env.supabase');
if (fs.existsSync(supabaseEnv)) {
  fs.copyFileSync(supabaseEnv, envOut);
  fs.appendFileSync(envOut, `\nDREAMLAND_ROOT_DOMAIN=${domain}\n`);
  fs.appendFileSync(envOut, `DREAMLAND_PWA_URL=https://${domain}\n`);
  fs.appendFileSync(envOut, `SITE_URL=https://${domain}\n`);
  fs.appendFileSync(envOut, `DREAMLAND_ADMIN_URL=https://${domain}/admin\n`);
  fs.appendFileSync(envOut, `DREAMLAND_API_URL=https://${domain}/api/v1\n`);
  fs.appendFileSync(envOut, 'DREAMLAND_STORAGE=wasabi\n');
} else {
  fs.copyFileSync(path.join(deploy, 'env.template'), envOut);
}

const pub = path.join(dist, 'public_html');
copyRecursive(web, pub, ['node_modules']);
const adminDir = path.join(pub, 'admin');
const apiDir = path.join(pub, 'api');
mkdirp(path.join(adminDir, 'assets'));
mkdirp(apiDir);

const ep = path.join(deploy, 'entrypoints');
fs.copyFileSync(path.join(ep, 'admin-index.php'), path.join(adminDir, 'index.php'));
fs.copyFileSync(path.join(ep, 'diagnose.php'), path.join(adminDir, 'diagnose.php'));
fs.copyFileSync(path.join(ep, 'admin-boot-test.php'), path.join(adminDir, 'boot-test.php'));
fs.copyFileSync(path.join(ep, 'admin-htaccess'), path.join(adminDir, '.htaccess'));
fs.copyFileSync(path.join(ep, 'api-index.php'), path.join(apiDir, 'index.php'));
fs.copyFileSync(path.join(ep, 'api-boot-test.php'), path.join(apiDir, 'boot-test.php'));
fs.copyFileSync(path.join(ep, 'api-htaccess'), path.join(apiDir, '.htaccess'));
fs.copyFileSync(path.join(ep, 'api-user.ini'), path.join(apiDir, '.user.ini'));
fs.copyFileSync(path.join(ep, 'api-user.ini'), path.join(adminDir, '.user.ini'));

rmrf(zipPath);
if (process.platform === 'win32') {
  execSync(
    `powershell -NoProfile -Command "Compress-Archive -Path '${dist.replace(/'/g, "''")}\\*' -DestinationPath '${zipPath.replace(/'/g, "''")}' -Force"`,
    { stdio: 'inherit' }
  );
} else {
  execSync(`cd "${dist}" && zip -r "${zipPath}" .`, { stdio: 'inherit' });
}

const mb = (fs.statSync(zipPath).size / (1024 * 1024)).toFixed(1);
console.log(`\nDone: ${zipPath} (${mb} MB)`);
console.log(`Admin: https://${domain}/admin/site/login`);
console.log(`API:   https://${domain}/api/v1/health`);
