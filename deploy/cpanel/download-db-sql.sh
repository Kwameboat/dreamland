#!/bin/bash
# Download Dreamland MySQL schema files from GitHub (if missing from upload zip).
# Usage: bash deploy/cpanel/download-db-sql.sh

set -e

HOME_DIR="${HOME:-/home/$(whoami)}"
DREAMLAND="${DREAMLAND_ROOT:-$HOME_DIR/dreamland}"
DB_DIR="$DREAMLAND/doc/db"
BASE_URL="https://raw.githubusercontent.com/Kwameboat/dreamland/main/backend/sayhi_v1.6_code/doc/db"

mkdir -p "$DB_DIR"
cd "$DB_DIR"

FILES=(
  sayhi_v1_6.sql
  dreamland_v1_migration.sql
  dreamland_v1_1_paystack.sql
  dreamland_rebrand.sql
  dreamland_v2_creator.sql
  dreamland_v3_live.sql
  dreamland_v4_engagement.sql
  dreamland_rejection_appeal.sql
)

echo "=== Downloading SQL schema to $DB_DIR ==="

for file in "${FILES[@]}"; do
  if [ -f "$file" ] && [ -s "$file" ]; then
    echo "OK (exists): $file"
    continue
  fi
  echo "Downloading $file ..."
  curl -fsSL -o "$file" "$BASE_URL/$file"
  if [ ! -s "$file" ]; then
    echo "FAILED: $file"
    exit 1
  fi
  echo "OK: $file ($(du -h "$file" | cut -f1))"
done

echo ""
echo "All SQL files ready. Run: bash setup-mysql.sh"
