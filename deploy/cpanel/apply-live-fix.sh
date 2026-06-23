#!/bin/bash
# Self-contained production repair for dreamlandgh.app
# cPanel Terminal:  curl -fsSL https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/apply-live-fix.sh | bash
set -e

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
ADMIN="$HOME_DIR/public_html/admin"
API="$HOME_DIR/public_html/api"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
TMP="/tmp/dreamland-live-fix-$$"

echo "=== Dreamland live fix (admin + API) ==="

if [ ! -d "$DL/vendor" ]; then
  echo "Missing $DL/vendor — run: cd ~/dreamland && composer install"
  exit 1
fi

echo ""
echo "--- 1. Fix folder permissions (required on this host) ---"
# api/, backend/, common/ sometimes lose the execute bit — blocks mkdir and file updates
chmod u+rwX "$DL" 2>/dev/null || true
for dir in api backend common console deploy doc scripts; do
  if [ -d "$DL/$dir" ]; then
    chmod u+rwX "$DL/$dir" 2>/dev/null || true
    find "$DL/$dir" -type d -exec chmod u+rwX {} \; 2>/dev/null || true
  fi
done
chmod -R u+rwX "$DL/api/runtime" "$DL/backend/runtime" "$DL/common/runtime" "$DL/backend/web/assets" 2>/dev/null || true
echo "Permissions updated."

echo ""
echo "--- 2. Download patches to /tmp (avoids permission errors) ---"
rm -rf "$TMP"
mkdir -p "$TMP/layouts" "$TMP/controllers" "$TMP/helpers" "$TMP/config" "$TMP/entrypoints"
curl -fsSL -o "$TMP/layouts/main-login.php" "$GITHUB/backend/sayhi_v1.6_code/backend/views/layouts/main-login.php"
curl -fsSL -o "$TMP/layouts/content.php" "$GITHUB/backend/sayhi_v1.6_code/backend/views/layouts/content.php"
curl -fsSL -o "$TMP/layouts/purchase-code.php" "$GITHUB/backend/sayhi_v1.6_code/backend/views/layouts/purchase-code.php"
curl -fsSL -o "$TMP/controllers/HealthController.php" "$GITHUB/backend/sayhi_v1.6_code/api/modules/v1/controllers/HealthController.php"
curl -fsSL -o "$TMP/helpers/DreamlandWasabiStorage.php" "$GITHUB/backend/sayhi_v1.6_code/common/helpers/DreamlandWasabiStorage.php"
curl -fsSL -o "$TMP/config/backend-subdir.php" "$GITHUB/deploy/cpanel/config/backend-subdir.php"
curl -fsSL -o "$TMP/entrypoints/admin-index.php" "$GITHUB/deploy/cpanel/entrypoints/admin-index.php"
curl -fsSL -o "$TMP/entrypoints/api-index.php" "$GITHUB/deploy/cpanel/entrypoints/api-index.php"
curl -fsSL -o "$TMP/entrypoints/api-boot-test.php" "$GITHUB/deploy/cpanel/entrypoints/api-boot-test.php"
echo "Downloaded to $TMP"

install_file() {
  local src="$1" dest="$2"
  local dest_dir
  dest_dir="$(dirname "$dest")"
  if [ ! -d "$dest_dir" ]; then
    chmod u+rwX "$(dirname "$dest_dir")" 2>/dev/null || true
    mkdir -p "$dest_dir" 2>/dev/null || {
      echo "FAIL: cannot write $dest (fix permissions on $(dirname "$dest_dir"))"
      return 1
    }
  fi
  cp -f "$src" "$dest" && chmod u+rw "$dest" && echo "OK: $dest"
}

echo ""
echo "--- 3. Install patched files ---"
install_file "$TMP/layouts/main-login.php" "$DL/backend/views/layouts/main-login.php"
install_file "$TMP/layouts/content.php" "$DL/backend/views/layouts/content.php"
install_file "$TMP/layouts/purchase-code.php" "$DL/backend/views/layouts/purchase-code.php"
install_file "$TMP/controllers/HealthController.php" "$DL/api/modules/v1/controllers/HealthController.php"
install_file "$TMP/helpers/DreamlandWasabiStorage.php" "$DL/common/helpers/DreamlandWasabiStorage.php"
install_file "$TMP/config/backend-subdir.php" "$DL/deploy/cpanel/config/backend-subdir.php"
install_file "$TMP/entrypoints/admin-index.php" "$ADMIN/index.php"
install_file "$TMP/entrypoints/api-index.php" "$API/index.php"
install_file "$TMP/entrypoints/api-boot-test.php" "$API/boot-test.php"

echo ""
echo "--- 4. Fallback sed patches (if GitHub copy missed) ---"
for layout in main-login.php content.php purchase-code.php; do
  f="$DL/backend/views/layouts/$layout"
  if [ -f "$f" ]; then
    sed -i 's/use dmstr\\widgets\\Alert;/use common\\widgets\\Alert;/' "$f"
  fi
done
WASABI="$DL/common/helpers/DreamlandWasabiStorage.php"
if [ -f "$WASABI" ]; then
  sed -i '/use Aws\\S3\\S3Client;/d' "$WASABI"
  sed -i 's/function createClient(?Setting $setting = null): S3Client/function createClient(?Setting $setting = null)/' "$WASABI"
  sed -i 's/return new S3Client(/return new \\Aws\\S3\\S3Client(/' "$WASABI"
fi
echo "Sed patches applied."

echo ""
echo "--- 5. Link admin CSS/JS ---"
mkdir -p "$DL/backend/web/assets" 2>/dev/null || true
chmod -R u+rwX "$DL/backend/web/assets" 2>/dev/null || true
for name in css img assets; do
  if [ -d "$DL/backend/web/$name" ]; then
    rm -rf "$ADMIN/$name"
    ln -sfn "$DL/backend/web/$name" "$ADMIN/$name"
    echo "OK: admin/$name"
  fi
done

echo ""
echo "--- 6. Composer ---"
cd "$DL"
if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -3
elif [ -f composer.phar ]; then
  php composer.phar install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -3
fi
php -r "require 'vendor/autoload.php'; echo 'AWS SDK: '.(class_exists('Aws\\S3\\S3Client')?'OK':'MISSING').PHP_EOL;"

echo ""
echo "--- 7. Seed demo admin ---"
if [ -f "$DL/scripts/seed-demo-data.php" ]; then
  php "$DL/scripts/seed-demo-data.php" 2>&1 | tail -5 || true
fi

rm -rf "$TMP"

echo ""
echo "--- 8. Verify ---"
curl -fsS "https://dreamlandgh.app/admin/diagnose.php" | grep -E "database:|vendor/" || true
echo ""
curl -fsS "https://dreamlandgh.app/api/v1/health" | head -c 500 || true
echo ""
curl -fsS -o /dev/null -w "admin login: HTTP %{http_code}\n" "https://dreamlandgh.app/admin/site/login" || true

echo ""
echo "Done."
echo "Admin: https://dreamlandgh.app/admin/site/login  (admin / demo123)"
echo "API:   https://dreamlandgh.app/api/v1/health"
