#!/bin/bash
# Fix wrong cPanel layout (public_html/public_html nested folder, dreamland inside public_html).
# Run in cPanel Terminal: bash fix-cpanel-layout.sh

set -e
HOME_DIR="${HOME:-/home/$(whoami)}"
cd "$HOME_DIR"

echo "=== Dreamland layout fix ==="

# 1. PHP app must live NEXT to public_html, not inside it
if [ -d public_html/dreamland ] && [ ! -d dreamland ]; then
  echo "Moving dreamland out of public_html..."
  mv public_html/dreamland dreamland
elif [ -d public_html/dreamland ] && [ -d dreamland ]; then
  echo "Removing duplicate public_html/dreamland (keeping ~/dreamland)..."
  rm -rf public_html/dreamland
fi

# 2. PWA files must be directly in public_html/, not public_html/public_html/
if [ -d public_html/public_html ]; then
  echo "Moving PWA from public_html/public_html/ to public_html/..."
  shopt -s dotglob
  for item in public_html/public_html/*; do
    name=$(basename "$item")
    if [ "$name" = "admin" ] || [ "$name" = "api" ]; then
      rm -rf "public_html/$name"
      cp -a "$item" "public_html/$name"
    else
      cp -a "$item" public_html/
    fi
  done
  rm -rf public_html/public_html
fi

# 3. Remove Namecheap default page if our index exists
if [ -f public_html/index.html ]; then
  rm -f public_html/index.php public_html/default.html public_html/parking-page.shtml 2>/dev/null || true
fi

# 4. Composer if vendor missing
if [ -d dreamland ] && [ ! -f dreamland/vendor/autoload.php ]; then
  echo "Running composer install..."
  cd dreamland
  if command -v composer >/dev/null 2>&1; then
    composer install --no-dev --optimize-autoloader --no-interaction
  elif [ -f /opt/cpanel/composer/bin/composer ]; then
    /opt/cpanel/composer/bin/composer install --no-dev --optimize-autoloader --no-interaction
  fi
  cd "$HOME_DIR"
fi

chmod -R 775 dreamland/api/runtime dreamland/backend/runtime dreamland/common/runtime 2>/dev/null || true

echo ""
echo "=== Verify ==="
ls -la public_html/index.html 2>/dev/null && echo "OK: PWA index.html at web root" || echo "MISSING: public_html/index.html"
ls -la public_html/admin/index.php 2>/dev/null && echo "OK: admin entry" || echo "MISSING: public_html/admin"
ls -la public_html/api/index.php 2>/dev/null && echo "OK: api entry" || echo "MISSING: public_html/api"
ls -la dreamland/vendor/autoload.php 2>/dev/null && echo "OK: composer vendor" || echo "MISSING: run composer in ~/dreamland"
echo ""
echo "Open: https://dreamlandgh.app"
echo "Admin: https://dreamlandgh.app/admin/site/login"
