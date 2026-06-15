# Dreamland

Ghana-focused short-video PWA — **Play, Watch, Earn**. Creators upload reels, go live, and monetize; viewers unlock premium content with credits.

## Stack

| Component | Tech | Local port |
|-----------|------|------------|
| PWA | Static HTML/JS/CSS | `:3000` |
| API | PHP 8.2 + Yii2 | `:8080` |
| Admin | Yii2 backend | `:8081` |
| Database | MySQL (local) / **Supabase Postgres** (prod) | `:3309` |
| Live | Node + mediasoup | `:4443` |
| Moderation | Node + Gemini | `:4444` |

## Quick start (local)

```powershell
cd dreamland
.\start-walkthrough.ps1
```

| Service | URL |
|---------|-----|
| PWA | http://localhost:3000 |
| API | http://localhost:8080/v1 |
| Admin | http://localhost:8081 |

### Demo logins

| Role | Email | Password |
|------|-------|----------|
| Viewer | `viewer@dreamland.app` | `demo123` |
| Creator | `creator@dreamland.app` | `demo123` |
| Admin | username `admin` | `demo123` |

Seed demo data:

```powershell
cd backend/sayhi_v1.6_code
php scripts/seed-demo-data.php
```

## Smoke test

```powershell
.\scripts\smoke-test.ps1
```

Checks PWA, API health, database connectivity, and demo viewer login.

## Deploy to GitHub

```bash
cd dreamland
git init
git add .
git commit -m "Initial Dreamland release"
git branch -M main
git remote add origin https://github.com/YOUR_USER/dreamland.git
git push -u origin main
```

**Do not commit:** `.env`, `params-local.php`, `main-local.php`, uploads, or `vendor/` / `node_modules/`.

Copy `env.example` → `.env` for local overrides.

## Deploy PWA to Vercel

1. Import the GitHub repo in [vercel.com](https://vercel.com)
2. **Root directory:** `dreamland` (or repo root if monorepo only contains dreamland)
3. Vercel reads `vercel.json` — builds `web/` via `node scripts/generate-vercel-env.js`
4. Set environment variables:

| Variable | Example |
|----------|---------|
| `DREAMLAND_API_URL` | `https://api.yourdomain.com/v1` |
| `DREAMLAND_UPLOADS_URL` | `https://api.yourdomain.com/frontend/web/uploads/image` |

5. Deploy — the PWA calls your external API (PHP cannot run on Vercel).

## Deploy API + Supabase

The Yii2 API and admin panel need a PHP host (**Railway**, **Render**, **Fly.io**, or Docker).

1. Create a [Supabase](https://supabase.com) project (PostgreSQL)
2. Port the MySQL schema to Postgres (see [`supabase/README.md`](supabase/README.md))
3. Apply Supabase migrations: `supabase db push`
4. On the API server, set env from `env.example` and use:
   - `common/config/params-supabase.example.php` → `params-local.php`
   - `common/config/main-local.example.php` → `main-local.php`
5. Enable `pdo_pgsql` and point `DB_*` at Supabase pooler (port **6543**)
6. Verify: `GET /v1/health` → `"database": true`

### Recommended production layout

```
Vercel          →  PWA (web/)
Railway/Render  →  PHP API + Admin
Supabase        →  PostgreSQL
Railway         →  live-server + moderation-agent (optional)
```

## CI

GitHub Actions workflow `.github/workflows/smoke-test.yml` runs on push/PR.

## Project layout

```
dreamland/
├── web/                 # PWA (Vercel)
├── backend/sayhi_v1.6_code/  # Yii2 API + admin
├── live-server/         # WebRTC live
├── moderation-agent/    # AI moderation
├── supabase/            # Postgres migrations + docs
├── scripts/             # smoke test, Vercel env, migrations
└── start-walkthrough.ps1
```

## License

Proprietary — Dreamland project.
