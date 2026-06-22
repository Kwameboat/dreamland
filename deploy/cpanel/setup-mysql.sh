#!/bin/bash
# Import Dreamland MySQL schema on cPanel (replaces Supabase).
# Run AFTER creating a MySQL database in cPanel and editing ~/dreamland/.env
#
# Usage:
#   cd ~/dreamland
#   bash setup-mysql.sh
#   php scripts/setup-cpanel-mysql.php

set -e

HOME_DIR="${HOME:-/home/$(whoami)}"
DREAMLAND="${DREAMLAND_ROOT:-$HOME_DIR/dreamland}"
cd "$DREAMLAND"

echo "=== Dreamland MySQL import ==="

load_env_file() {
  local file="$1"
  [ -f "$file" ] || return 0
  while IFS= read -r line || [ -n "$line" ]; do
    line="${line%$'\r'}"
    case "$line" in
      ''|\#*) continue ;;
    esac
    if [[ "$line" != *"="* ]]; then
      continue
    fi
    local key="${line%%=*}"
    local val="${line#*=}"
    key="$(echo "$key" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"
    val="$(echo "$val" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//' | sed 's/^["'\'']//;s/["'\'']$//')"
    if [ -n "$key" ] && [ -z "${!key:-}" ]; then
      export "$key=$val"
    fi
  done < "$file"
}

if [ -f .env ]; then
  load_env_file .env
else
  echo "Missing .env — creating from template is OK if you pass DB_* below."
fi

DB_DRIVER="${DB_DRIVER:-mysql}"
if [ "$DB_DRIVER" = "pgsql" ]; then
  echo "WARNING: .env still has DB_DRIVER=pgsql — switching to mysql for cPanel."
  DB_DRIVER=mysql
fi

DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:?Set DB_NAME in .env (e.g. dreaxdjo_app)}"
DB_USER="${DB_USER:?Set DB_USER in .env (e.g. dreaxdjo_user)}"
DB_PASSWORD="${DB_PASSWORD:?Set DB_PASSWORD in .env}"

MYSQL_CMD=(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME")

echo "Using DB: ${DB_USER}@${DB_HOST}:${DB_PORT}/${DB_NAME}"

echo "Testing MySQL connection..."
if ! "${MYSQL_CMD[@]}" -e "SELECT 1" >/dev/null 2>&1; then
  echo "MySQL connection failed. Check DB_HOST, DB_USER, DB_PASSWORD in .env"
  echo "cPanel MySQL host is usually: localhost"
  exit 1
fi
echo "OK: connected to $DB_NAME"

DB_DIR="$DREAMLAND/doc/db"
if [ ! -d "$DB_DIR" ]; then
  echo "Missing $DB_DIR — re-upload dreamland package."
  exit 1
fi

import_sql() {
  local file="$1"
  local path="$DB_DIR/$file"
  if [ ! -f "$path" ]; then
    echo "SKIP (missing): $file"
    return 0
  fi
  echo "Importing $file ..."
  "${MYSQL_CMD[@]}" < "$path"
  echo "OK: $file"
}

TABLE_COUNT=$("${MYSQL_CMD[@]}" -N -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user'" 2>/dev/null || echo 0)

if [ "${TABLE_COUNT:-0}" -gt 0 ]; then
  echo "Base schema already present (user table exists). Skipping sayhi_v1_6.sql"
else
  import_sql "sayhi_v1_6.sql"
fi

for extra in \
  dreamland_v1_migration.sql \
  dreamland_v1_1_paystack.sql \
  dreamland_rebrand.sql \
  dreamland_v2_creator.sql \
  dreamland_v3_live.sql \
  dreamland_v4_engagement.sql \
  dreamland_rejection_appeal.sql
do
  import_sql "$extra"
done

echo ""
echo "=== SQL import complete ==="
echo "Next: php scripts/setup-cpanel-mysql.php"
echo "Then: https://dreamlandgh.app/admin/diagnose.php"
