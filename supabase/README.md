# Supabase setup for Dreamland

Dreamland's Yii2 backend currently uses **MySQL** locally. Production targets **Supabase PostgreSQL**.

## Architecture

| Layer | Host | Notes |
|-------|------|--------|
| **PWA** | Vercel | Static `web/` — set `DREAMLAND_API_URL` |
| **API + Admin** | Railway / Render / Fly.io / Docker | PHP 8.2 + Yii2 — **not** Vercel |
| **Database** | Supabase Postgres | Connection pooler on port **6543** |
| **Live / Moderation** | Railway or separate Node hosts | Optional for full features |

## 1. Create Supabase project

1. [supabase.com/dashboard](https://supabase.com/dashboard) → New project
2. Save **Database password** and **Project URL**
3. Copy keys from **Settings → API** (`anon`, `service_role`)

## 2. Link CLI (optional)

```bash
npm i -g supabase
cd dreamland
supabase login
supabase link --project-ref YOUR_PROJECT_REF
```

## 3. Apply schema

The full SayHi/Yii2 schema lives in MySQL format under:

```
backend/sayhi_v1.6_code/doc/db/
```

**Recommended path for production:**

1. Spin up local MySQL and apply all Dreamland migration scripts (see `README.md`).
2. Export structure + seed with your DBA tool, then port to PostgreSQL (types: `TINYINT` → `SMALLINT`, `AUTO_INCREMENT` → `SERIAL`, etc.).
3. Or use a migration service (e.g. pgloader) from MySQL → Supabase Postgres.

Dreamland-specific columns/tables are applied by:

```bash
cd backend/sayhi_v1.6_code
php scripts/apply-dreamland-v2-migration.php
php scripts/apply-dreamland-v3-migration.php
php scripts/apply-dreamland-moderation-migration.php
php scripts/apply-dreamland-push-migration.php
php scripts/apply-dreamland-creator-approval-migration.php
php scripts/apply-dreamland-rejection-migration.php
php scripts/seed-demo-data.php
```

Port the resulting MySQL schema to Postgres before pointing production at Supabase.

## 4. Configure Yii2 for Supabase Postgres

Copy `backend/sayhi_v1.6_code/common/config/params-supabase.example.php` to `params-local.php` on your API server and set env vars from `env.example`.

DSN example (direct):

```
pgsql:host=db.YOUR_REF.supabase.co;port=5432;dbname=postgres
```

Pooler (recommended for PHP):

```
pgsql:host=aws-0-REGION.pooler.supabase.com;port=6543;dbname=postgres
```

Enable PHP `pdo_pgsql` on your API host.

## 5. Supabase features (optional)

- **Auth**: Can replace custom JWT later; PWA currently uses Yii2 bearer tokens.
- **Storage**: Move `frontend/web/uploads/` to Supabase Storage buckets for CDN delivery.
- **Realtime**: Live chat / notifications can use Supabase Realtime alongside the Node live server.

## 6. Verify connection

From API server after deploy:

```bash
curl https://your-api.example.com/v1/health
# checks.database should be true
```
