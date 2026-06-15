# Dreamland deployment guide

Step-by-step instructions for **GitHub**, **Vercel** (PWA), **Supabase** (database), and **Railway** (PHP API + admin).

---

## Architecture

```
┌─────────────┐     HTTPS      ┌──────────────────┐
│   Vercel    │ ──────────────►│  Railway/Render  │
│  PWA (web/) │   API calls    │  PHP API :8080   │
└─────────────┘                │  Admin  :8081    │
                               └────────┬─────────┘
                                        │
                                        ▼
                               ┌──────────────────┐
                               │    Supabase      │
                               │   PostgreSQL     │
                               └──────────────────┘
```

| Service | Host | What runs |
|---------|------|-----------|
| **Vercel** | Edge CDN | Static PWA only |
| **Railway** | Container | Yii2 API + admin (PHP 8.2) |
| **Supabase** | Managed DB | PostgreSQL |
| **Railway** (optional) | Node | live-server, moderation-agent |

---

## 1. GitHub

### Push the repo

```bash
cd dreamland
git add .
git commit -m "Initial Dreamland release"
git branch -M main
git remote add origin https://github.com/YOUR_USER/dreamland.git
git push -u origin main
```

### Secrets — never commit

- `.env`
- `backend/sayhi_v1.6_code/common/config/params-local.php`
- `backend/sayhi_v1.6_code/common/config/main-local.php`
- `moderation-agent/.env`

Use `env.example` as the template for all hosts.

---

## 2. Supabase (database)

### Create project

1. Go to [supabase.com/dashboard](https://supabase.com/dashboard)
2. **New project** → choose region close to users (e.g. EU or US)
3. Save the **database password**

### Get connection details

**Settings → Database:**

| Setting | Where to find it |
|---------|------------------|
| Host | `db.YOUR_REF.supabase.co` |
| Port (direct) | `5432` |
| Port (pooler) | `6543` — use this for PHP |
| Database | `postgres` |
| User | `postgres` or `postgres.YOUR_REF` (pooler) |

**Settings → API:**

| Key | Use |
|-----|-----|
| `anon` | Client-side (future Supabase Auth) |
| `service_role` | Server-only — never expose in PWA |

### Schema migration (MySQL → Postgres)

Local dev uses **MySQL**. Production uses **Postgres**. You must port the schema once:

```powershell
# Local: apply all Dreamland migrations on MySQL first
cd backend/sayhi_v1.6_code
docker compose -f docker-compose.mysql-local.yml up -d
php scripts/apply-dreamland-v2-migration.php
php scripts/apply-dreamland-v3-migration.php
php scripts/apply-dreamland-moderation-migration.php
php scripts/apply-dreamland-push-migration.php
php scripts/apply-dreamland-creator-approval-migration.php
php scripts/apply-dreamland-rejection-migration.php
php scripts/seed-demo-data.php
```

Then export/port to Postgres using one of:

- **pgloader** (MySQL → Postgres)
- Manual conversion of `doc/db/*.sql` (adjust types: `TINYINT` → `SMALLINT`, etc.)
- DBA review

Apply Dreamland Postgres extensions:

```bash
npm i -g supabase
cd dreamland
supabase login
supabase link --project-ref YOUR_PROJECT_REF
supabase db push
```

See also `supabase/README.md`.

### Supabase env vars (for API server)

```env
DB_DRIVER=pgsql
DB_HOST=aws-0-REGION.pooler.supabase.com
DB_PORT=6543
DB_NAME=postgres
DB_USER=postgres.YOUR_PROJECT_REF
DB_PASSWORD=your-db-password
SUPABASE_URL=https://YOUR_REF.supabase.co
SUPABASE_ANON_KEY=eyJ...
SUPABASE_SERVICE_ROLE_KEY=eyJ...
```

---

## 3. Railway (PHP API + admin)

Vercel cannot run PHP. Deploy the Yii2 backend on Railway (or Render / Fly.io).

### Option A — Docker (recommended)

1. [railway.app](https://railway.app) → **New project** → **Deploy from GitHub**
2. Select your `dreamland` repo
3. Add a service using `backend/sayhi_v1.6_code/api/Dockerfile` (or root Dockerfile if you add one)
4. Set **Root directory** to `backend/sayhi_v1.6_code`

### Environment variables (Railway)

Copy from `env.example`:

```env
YII_ENV=prod
YII_DEBUG=0
SITE_URL=https://your-api.up.railway.app
DREAMLAND_DEV_MODE=0

DB_DRIVER=pgsql
DB_HOST=aws-0-REGION.pooler.supabase.com
DB_PORT=6543
DB_NAME=postgres
DB_USER=postgres.YOUR_PROJECT_REF
DB_PASSWORD=...

PAYSTACK_PUBLIC_KEY=pk_live_...
PAYSTACK_SECRET_KEY=sk_live_...
GEMINI_API_KEY=...
DREAMLAND_MOD_SECRET=long-random-string
DREAMLAND_LIVE_SECRET=long-random-string
```

### Yii2 config on server

Create these on the server (or via deploy script) from templates:

```bash
cp common/config/params-supabase.example.php common/config/params-local.php
cp common/config/main-local.example.php common/config/main-local.php
# Edit params-local.php with production URLs
composer install --no-dev --optimize-autoloader
```

### Verify API

```bash
curl https://your-api.up.railway.app/v1/health
```

Expect `"database": true` and `"status": "ok"` inside `data`.

### Admin panel

Run a second Railway service (or same container on another port) with:

```bash
php -S 0.0.0.0:8081 -t backend/web backend/web/router.php
```

Or nginx routing `/admin` → `backend/web`.

---

## 4. Vercel (PWA)

### Import project

1. [vercel.com/new](https://vercel.com/new) → Import GitHub repo
2. **Root Directory:** `dreamland` (if repo root is parent, set accordingly)
3. Framework: **Other** — `vercel.json` handles build

### Build settings (auto from vercel.json)

| Setting | Value |
|---------|--------|
| Build command | `node scripts/generate-vercel-env.js` |
| Output directory | `web` |

### Environment variables

| Name | Value | Example |
|------|--------|---------|
| `DREAMLAND_API_URL` | Your Railway API base | `https://dreamland-api.up.railway.app/v1` |
| `DREAMLAND_UPLOADS_URL` | Media base URL | `https://dreamland-api.up.railway.app/frontend/web/uploads/image` |

### Deploy

Click **Deploy**. Vercel generates `web/env-config.js` at build time; the PWA reads `window.__DL_ENV__.api`.

### Custom domain (optional)

1. Vercel → Project → **Domains** → add `app.yourdomain.com`
2. Update `SITE_URL` and Paystack callback URLs on the API

---

## 5. Optional services

### Moderation agent (Gemini)

Deploy `moderation-agent/` on Railway:

```env
GEMINI_API_KEY=...
DREAMLAND_MOD_PORT=4444
DREAMLAND_MOD_SECRET=same-as-api
DB_HOST=...supabase...
DB_PORT=6543
```

Set on API server:

```env
DREAMLAND_MODERATION_AGENT_URL=https://mod-agent.up.railway.app
```

### Live server (WebRTC)

Deploy `live-server/` on Railway with UDP/TCP ports exposed.

```env
DREAMLAND_LIVE_SERVER_URL=https://live.up.railway.app
DREAMLAND_LIVE_SIGNING_URL=https://live.up.railway.app
```

---

## 6. Post-deploy checklist

- [ ] `GET /v1/health` → database true
- [ ] PWA loads at Vercel URL
- [ ] Login works (viewer / creator demo or real accounts)
- [ ] Upload reel → appears in admin moderation queue
- [ ] Reject with reason → creator sees notification in Studio
- [ ] Paystack keys set for production wallet (disable `DREAMLAND_DEV_MODE`)
- [ ] CORS: API allows Vercel origin (Yii2 CORS config if needed)
- [ ] Uploads directory writable on API host OR migrate to Supabase Storage

### Smoke test (production)

```powershell
$env:DL_API_BASE = "https://your-api.up.railway.app/v1"
$env:DL_PWA_URL = "https://your-app.vercel.app"
.\scripts\smoke-test.ps1
```

---

## 7. CI (GitHub Actions)

On every push to `main`, `.github/workflows/smoke-test.yml` runs:

- Composer install
- PHP built-in server
- Health check
- Static PWA file checks

Add Railway/Vercel deploy hooks separately when ready.

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| PWA shows "API offline" | Check `DREAMLAND_API_URL` on Vercel; redeploy |
| Health `database: false` | Verify Supabase pooler credentials; enable `pdo_pgsql` |
| Upload fails | PHP `upload_max_filesize` / disk space on API host |
| CORS errors | Add Vercel domain to API CORS allowed origins |
| Studio blank | API must return 200 on `/v1/creator/dashboard` |

---

## Local development (unchanged)

```powershell
.\start-walkthrough.ps1
# PWA :3000 | API :8080 | Admin :8081
```

Demo logins: `viewer@dreamland.app` / `creator@dreamland.app` — password `demo123`
