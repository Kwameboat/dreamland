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

**Option A — cPanel File Manager**
1. Log in: https://server360.web-hosting.com/cpanel
2. File Manager → `/home/dreaxdjo/`
3. Upload `dreamland-cpanel.zip` → Extract
4. Ensure `dreamland/` sits next to `public_html/` (not inside it)
5. Copy everything from extracted `public_html/` into your live `public_html/`

**Option B — FTP script**
1. Copy `deploy/cpanel/ftp-credentials.example.json` → `ftp-credentials.local.json`
2. Fill username/password (never commit this file)
3. `.\scripts\deploy-cpanel-ftp.ps1`

## 3. Configure `.env`

Edit `/home/dreaxdjo/dreamland/.env`:

| Variable | Value |
|----------|--------|
| `DREAMLAND_PWA_URL` | `https://dreamlandgh.app` |
| `SITE_URL` | `https://dreamlandgh.app` |
| `DREAMLAND_STORAGE` | `wasabi` |
| `WASABI_*` | Your Wasabi keys |
| `DB_*` | Supabase or cPanel MySQL |
| `COOKIE_VALIDATION_KEY` | Random 64-char hex |

Generate cookie key:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

## 4. Install PHP dependencies (required once)

cPanel → **Terminal** (or SSH):

```bash
cd ~/dreamland
composer install --no-dev --optimize-autoloader
```

If `composer` is not found, use cPanel → **PHP Composer** → select `~/dreamland` → Install.

## 5. PHP version

cPanel → **Select PHP Version** → set **8.2** for:
- `public_html/admin`
- `public_html/api`

## 6. Wasabi (all media)

1. Create bucket at [wasabi.com](https://wasabi.com)
2. Add public-read bucket policy (see `DreamlandWasabiStorage::PUBLIC_READ_POLICY_TEMPLATE`)
3. Put keys in `dreamland/.env` OR Admin → Settings → Storage → Wasabi

## 7. Verify

| URL | Expected |
|-----|----------|
| https://dreamlandgh.app | PWA loads |
| https://dreamlandgh.app/api/v1/health | `"database": true`, `"wasabi_storage": true` |
| https://dreamlandgh.app/admin/site/login | Admin login |

Default admin: `admin` / `demo123` (change after first login)

## 8. Security

- Change cPanel password after setup
- Change admin password
- Do not commit `.env` or FTP credentials
