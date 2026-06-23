#!/bin/bash
# One-shot repair for dreamlandgh.app admin + API on cPanel.
# Run: bash ~/dreamland/deploy/cpanel/fix-production.sh
set -e

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
ADMIN="$HOME_DIR/public_html/admin"
API="$HOME_DIR/public_html/api"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"

echo "=== Dreamland production fix ==="
echo "App: $DL"

if [ ! -d "$DL" ]; then
  echo "Missing $DL"
  exit 1
fi

echo ""
echo "--- 1. Permissions ---"
chmod u+x "$DL/backend/web" "$DL/deploy" "$DL/deploy/cpanel" "$DL/deploy/cpanel/config" "$DL/doc" 2>/dev/null || true
chmod -R u+rwX "$DL/api/runtime" "$DL/backend/runtime" "$DL/common/runtime" "$DL/backend/web/assets" 2>/dev/null || true
chmod -R u+rwX "$DL/deploy" "$DL/doc" 2>/dev/null || true

echo ""
echo "--- 2. Fix Alert widget (admin 500 on login) ---"
for layout in main-login.php content.php purchase-code.php; do
  file="$DL/backend/views/layouts/$layout"
  if [ -f "$file" ]; then
    sed -i 's/use dmstr\\widgets\\Alert;/use common\\widgets\\Alert;/' "$file"
    echo "OK: $layout"
  fi
done

echo ""
echo "--- 3. Link admin static assets ---"
bash "$DL/deploy/cpanel/link-admin-assets.sh" 2>/dev/null || bash "$HOME_DIR/link-admin-assets.sh" 2>/dev/null || {
  mkdir -p "$DL/backend/web/assets"
  chmod -R u+rwX "$DL/backend/web/assets"
  for name in css img assets; do
    if [ -d "$DL/backend/web/$name" ]; then
      rm -rf "$ADMIN/$name"
      ln -sfn "$DL/backend/web/$name" "$ADMIN/$name"
      echo "OK: linked admin/$name"
    fi
  done
}

echo ""
echo "--- 4. Update entrypoints + config ---"
mkdir -p "$DL/deploy/cpanel/config" "$DL/deploy/cpanel/entrypoints"
fetch() {
  local url="$1" dest="$2"
  if curl -fsSL -o "$dest" "$url"; then
    echo "OK: $(basename "$dest")"
  else
    echo "SKIP (download failed): $url"
  fi
}

fetch "$GITHUB/deploy/cpanel/entrypoints/admin-index.php" "$ADMIN/index.php"
fetch "$GITHUB/deploy/cpanel/entrypoints/api-index.php" "$API/index.php"
fetch "$GITHUB/deploy/cpanel/entrypoints/admin-boot-test.php" "$ADMIN/boot-test.php"
fetch "$GITHUB/deploy/cpanel/entrypoints/api-boot-test.php" "$API/boot-test.php"
fetch "$GITHUB/deploy/cpanel/entrypoints/diagnose.php" "$ADMIN/diagnose.php"
fetch "$GITHUB/deploy/cpanel/config/backend-subdir.php" "$DL/deploy/cpanel/config/backend-subdir.php"
fetch "$GITHUB/deploy/cpanel/config/api-subdir.php" "$DL/deploy/cpanel/config/api-subdir.php"
fetch "$GITHUB/deploy/cpanel/fix-production.sh" "$DL/deploy/cpanel/fix-production.sh"
HEALTH_SRC="$GITHUB/backend/sayhi_v1.6_code/api/modules/v1/controllers/HealthController.php"
HEALTH_DEST="$DL/api/modules/v1/controllers/HealthController.php"
if [ ! -f "$HEALTH_DEST" ]; then
  HEALTH_DEST="$DL/backend/sayhi_v1.6_code/api/modules/v1/controllers/HealthController.php"
fi
fetch "$HEALTH_SRC" "$HEALTH_DEST"

echo ""
echo "--- 5. Composer (AWS SDK + vendor) ---"
cd "$DL"
if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -5
elif [ -f /opt/cpanel/composer/bin/composer ]; then
  /opt/cpanel/composer/bin/composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -5
elif [ -f composer.phar ]; then
  php composer.phar install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -5
else
  echo "WARN: composer not found — vendor may be incomplete"
fi

php -r "require 'vendor/autoload.php'; echo 'AWS SDK: '.(class_exists('Aws\\S3\\S3Client')?'OK':'MISSING').PHP_EOL;"

echo ""
echo "--- 6. Seed admin user (if missing) ---"
if [ -f "$DL/scripts/seed-demo-data.php" ]; then
  php "$DL/scripts/seed-demo-data.php" || true
elif [ -f "$DL/backend/sayhi_v1.6_code/scripts/seed-demo-data.php" ]; then
  php "$DL/backend/sayhi_v1.6_code/scripts/seed-demo-data.php" || true
fi

echo ""
echo "--- 7. Smoke checks ---"
curl -fsS "https://dreamlandgh.app/admin/diagnose.php" | head -20 || true
echo ""
curl -fsS "https://dreamlandgh.app/api/v1/health" || true
echo ""
curl -fsS -o /dev/null -w "admin login HTTP %{http_code}\n" "https://dreamlandgh.app/admin/site/login" || true

echo ""
echo "=== Done ==="
echo "Admin login: https://dreamlandgh.app/admin/site/login  (admin / demo123)"
echo "API health:  https://dreamlandgh.app/api/v1/health"
echo "Boot tests:  /admin/boot-test.php  /api/boot-test.php"
