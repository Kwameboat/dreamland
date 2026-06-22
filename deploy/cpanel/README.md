# Dreamland on Namecheap / cPanel — dreamlandgh.app

## Layout on server

```
/home/dreaxdjo/
  dreamland/          ← PHP app (NOT web-accessible)
  public_html/        ← PWA at https://dreamlandgh.app
    index.html
    js/, css/, ...
    admin/            ← Admin panel https://dreamlandgh.app/admin
    api/              ← API https://dreamlandgh.app/api/v1
```

## 1. Build locally

```powershell
cd dreamland
.\scripts\build-cpanel-package.ps1
```

Creates `dist/dreamland-cpanel.zip`.

## 2. Upload

**Option A — cPanel File Manager (easiest)**
1. Log in: https://server360.web-hosting.com/cpanel
2. File Manager → `/home/dreaxdjo/`
3. Upload `dist/dreamland-cpanel.zip`
4. Open **Terminal** in cPanel and run:
   ```bash
   cd ~
   bash dreamland/deploy/cpanel/remote-install.sh
   ```
   (Or upload `deploy/cpanel/remote-install.sh` first, then `bash remote-install.sh`)

**Option B — SSH from your PC (port 21098, not 22)**
```powershell
$env:CPANEL_SSH_PASSWORD = 'your-current-cpanel-password'
$env:CPANEL_SSH_PORT = '21098'
npm run deploy:cpanel
```

**Option C — FTP script** (if FTP works)
```powershell
node scripts/deploy-cpanel-ftp.js
```

## 3. Database — cPanel MySQL (not Supabase)

Namecheap shared hosting **blocks outbound connections to Supabase**. Use local MySQL:

1. cPanel → **MySQL Databases**
2. Create database: `dreamland`
3. Create user + password → add user to database with **ALL PRIVILEGES**
4. Note the full prefixed names (e.g. `dreaxdjo_dreamland`, `dreaxdjo_dluser`)

## 4. Configure `.env`

Edit `/home/dreaxdjo/dreamland/.env`:

| Variable | Value |
|----------|--------|
| `DREAMLAND_PWA_URL` | `https://dreamlandgh.app` |
| `SITE_URL` | `https://dreamlandgh.app` |
| `DREAMLAND_STORAGE` | `wasabi` |
| `WASABI_*` | Your Wasabi keys |
| `DB_DRIVER` | `mysql` |
| `DB_HOST` | `localhost` |
| `DB_NAME` | `dreaxdjo_dreamland` (your cPanel DB name) |
| `DB_USER` | `dreaxdjo_dluser` (your cPanel DB user) |
| `DB_PASSWORD` | Your MySQL password |
| `COOKIE_VALIDATION_KEY` | Random 64-char hex |

Remove or comment out any `DB_HOST=...supabase...` lines.

Generate cookie key:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

## 5. Import MySQL schema + seed data

cPanel → **Terminal**:

```bash
cd ~/dreamland
bash deploy/cpanel/setup-mysql.sh
php scripts/setup-cpanel-mysql.php
```

If `deploy/cpanel/setup-mysql.sh` is missing, download it:

```bash
curl -sO https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/setup-mysql.sh
bash setup-mysql.sh
```

## 6. Install PHP dependencies (required once)

cPanel → **Terminal** (or SSH):

```bash
cd ~/dreamland
composer install --no-dev --optimize-autoloader
```

If `composer` is not found:

```bash
cd ~/dreamland
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev --optimize-autoloader
```

## 7. PHP version

cPanel → **Select PHP Version** → set **8.2** for:
- `public_html/admin`
- `public_html/api`

## 6. Wasabi (all media)

1. Create bucket at [wasabi.com](https://wasabi.com)
2. Add public-read bucket policy (see `DreamlandWasabiStorage::PUBLIC_READ_POLICY_TEMPLATE`)
3. Put keys in `dreamland/.env` OR Admin → Settings → Storage → Wasabi

## 8. Verify

| URL | Expected |
|-----|----------|
| https://dreamlandgh.app | PWA loads |
| https://dreamlandgh.app/api/v1/health | `"database": true`, `"wasabi_storage": true` |
| https://dreamlandgh.app/admin/site/login | Admin login |

Default admin: `admin` / `demo123` (change after first login)

## 9. Security

- Change cPanel password after setup
- Change admin password
- Do not commit `.env` or FTP credentials
