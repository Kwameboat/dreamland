#!/bin/bash
# Self-contained production repair for dreamlandgh.app (no GitHub required).
# Paste into cPanel Terminal:  bash ~/dreamland/deploy/cpanel/apply-live-fix.sh
# Or: curl -sL ... | bash   after uploading this file once.
set -e

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
ADMIN="$HOME_DIR/public_html/admin"
API="$HOME_DIR/public_html/api"

echo "=== Dreamland live fix (admin + API) ==="

if [ ! -d "$DL/vendor" ]; then
  echo "Missing $DL/vendor — run composer install first."
  exit 1
fi

echo ""
echo "--- Permissions ---"
chmod u+x "$DL/backend/web" "$DL/deploy" "$DL/doc" 2>/dev/null || true
chmod -R u+rwX "$DL/api/runtime" "$DL/backend/runtime" "$DL/common/runtime" "$DL/backend/web/assets" 2>/dev/null || true
chmod -R u+rwX "$DL/deploy" "$DL/doc" 2>/dev/null || true

echo ""
echo "--- Fix admin login (Alert widget class) ---"
for layout in main-login.php content.php purchase-code.php; do
  f="$DL/backend/views/layouts/$layout"
  if [ -f "$f" ]; then
    sed -i 's/use dmstr\\widgets\\Alert;/use common\\widgets\\Alert;/' "$f"
    echo "OK: $layout"
  fi
done

echo ""
echo "--- Fix API health (AWS SDK optional load) ---"
WASABI="$DL/common/helpers/DreamlandWasabiStorage.php"
if [ -f "$WASABI" ]; then
  sed -i '/use Aws\\S3\\S3Client;/d' "$WASABI"
  sed -i 's/function createClient(?Setting $setting = null): S3Client/function createClient(?Setting $setting = null)/' "$WASABI"
  sed -i 's/return new S3Client(/return new \\Aws\\S3\\S3Client(/' "$WASABI"
  echo "OK: DreamlandWasabiStorage.php"
fi

echo ""
echo "--- Link admin CSS/JS ---"
mkdir -p "$DL/backend/web/assets"
chmod -R u+rwX "$DL/backend/web/assets"
for name in css img assets; do
  if [ -d "$DL/backend/web/$name" ]; then
    rm -rf "$ADMIN/$name"
    ln -sfn "$DL/backend/web/$name" "$ADMIN/$name"
    echo "OK: admin/$name"
  fi
done

echo ""
echo "--- Composer (ensure AWS SDK in vendor) ---"
cd "$DL"
if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -3
elif [ -f composer.phar ]; then
  php composer.phar install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -3
fi
php -r "require 'vendor/autoload.php'; echo 'AWS SDK: '.(class_exists('Aws\\S3\\S3Client')?'OK':'MISSING').PHP_EOL;"

echo ""
echo "--- Seed demo admin if needed ---"
if [ -f "$DL/scripts/seed-demo-data.php" ]; then
  php "$DL/scripts/seed-demo-data.php" 2>&1 | tail -5 || true
fi

echo ""
echo "--- Verify ---"
curl -fsS "https://dreamlandgh.app/admin/diagnose.php" | grep -E "database:|vendor/" || true
echo ""
curl -fsS "https://dreamlandgh.app/api/v1/health" | head -c 400 || true
echo ""
curl -fsS -o /dev/null -w "admin login: HTTP %{http_code}\n" "https://dreamlandgh.app/admin/site/login" || true

echo ""
echo "Done."
echo "Admin: https://dreamlandgh.app/admin/site/login  (admin / demo123)"
echo "API:   https://dreamlandgh.app/api/v1/health"
