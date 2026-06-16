# Supabase setup for Dreamland

Connect Dreamland's Yii2 API to **Supabase PostgreSQL**.

## Quick start

### 1. Create Supabase project

1. [supabase.com/dashboard](https://supabase.com/dashboard) → **New project**
2. Save your **database password**
3. Open **Project Settings → Database → Connection string**
4. Copy the **Transaction pooler** URI (port **6543**) — best for PHP

### 2. Configure env

```powershell
cd dreamland
copy .env.supabase.example .env.supabase
# Edit .env.supabase with your Supabase credentials
```

Or set in PowerShell before running scripts:

```powershell
$env:DATABASE_URL = "postgresql://postgres.YOUR_REF:YOUR_PASSWORD@aws-0-REGION.pooler.supabase.com:6543/postgres"
```

### 3. Build schema (one-time)

From the bundled SayHi SQL dump (no Docker required):

```powershell
php scripts/build-core-schema-from-sayhi.php
php scripts/export-mysql-to-supabase.php   # copies Dreamland v1–v4 migrations
```

Or export from local Docker MySQL if you already applied all Dreamland migrations there:

```powershell
docker start dreamland-mysql
php scripts/export-mysql-to-supabase.php
```

### 4. Apply migrations to Supabase

```powershell
$env:DATABASE_URL = "postgresql://..."   # from Supabase dashboard
php scripts/apply-supabase.php
php scripts/seed-supabase-demo.php
```

### 5. Point Yii2 API at Supabase

On your API server (Railway/local):

```powershell
copy backend\sayhi_v1.6_code\common\config\params-supabase.example.php backend\sayhi_v1.6_code\common\config\params-local.php
copy backend\sayhi_v1.6_code\common\config\main-local.example.php backend\sayhi_v1.6_code\common\config\main-local.php
```

Set env vars from `.env.supabase.example`:

| Variable | Value |
|----------|--------|
| `DB_DRIVER` | `pgsql` |
| `DB_HOST` | pooler host from Supabase |
| `DB_PORT` | `6543` |
| `DB_NAME` | `postgres` |
| `DB_USER` | `postgres.YOUR_PROJECT_REF` |
| `DB_PASSWORD` | your DB password |

Enable PHP extension: **pdo_pgsql**

### 6. Verify

```bash
curl https://your-api.example.com/v1/health
# data.checks.database should be true
```

---

## Migration files

| File | Contents |
|------|----------|
| `000001_core_schema.sql` | Auto-exported from MySQL (user, post, etc.) |
| `000002_dreamland_v1.sql` | Credits, paywall, safety queue |
| `000003_dreamland_v2_v4.sql` | Creator, live, engagement, appeals |
| `000004_dreamland_extensions.sql` | Extra columns |
| `000005_seed_demo.sql` | Settings rebrand (optional) |

---

## Supabase CLI (optional)

```bash
npm i -g supabase
supabase login
supabase link --project-ref YOUR_PROJECT_REF
supabase db push
```

---

## Demo logins (after seed)

| Email | Password |
|-------|----------|
| viewer@dreamland.app | demo123 |
| creator@dreamland.app | demo123 |

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| `connection refused` | Use pooler port 6543, not 5432 direct |
| `relation "user" does not exist` | Run `apply-supabase.php` first |
| `duplicate column` | Migration already applied — safe to skip |
| Yii2 errors on Postgres | Ensure `pdo_pgsql` enabled; check `main-local.example.php` schemaMap |

See also: [DEPLOY.md](../DEPLOY.md)
