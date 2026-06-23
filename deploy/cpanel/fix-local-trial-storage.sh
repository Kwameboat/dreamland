#!/bin/bash
# Trial mode: store reels on cPanel disk (not Wasabi). Symlink uploads for direct Apache serve.
# Run: curl -fsSL https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-local-trial-storage.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
WEB="${DREAMLAND_WEB_DIR:-$HOME_DIR/public_html}"
API="$WEB/api"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
TMP="/tmp/dreamland-local-storage-$$"
BASE="$GITHUB/backend/sayhi_v1.6_code"
WEB_BASE="$GITHUB/web"

mkdir -p "$TMP"
trap 'rm -rf "$TMP"' EXIT

echo "=== Dreamland local trial storage (cPanel disk) ==="

fetch() { curl -fsSL -o "$1" "$2"; }

fetch "$TMP/DreamlandStorageMode.php" "$BASE/common/helpers/DreamlandStorageMode.php"
fetch "$TMP/DreamlandMediaUrl.php" "$BASE/common/helpers/DreamlandMediaUrl.php"
fetch "$TMP/DreamlandWasabiStorage.php" "$BASE/common/helpers/DreamlandWasabiStorage.php"
fetch "$TMP/FileUpload.php" "$BASE/common/components/FileUpload.php"
fetch "$TMP/MediaController.php" "$BASE/api/modules/v1/controllers/MediaController.php"
fetch "$TMP/HealthController.php" "$BASE/api/modules/v1/controllers/HealthController.php"
fetch "$TMP/CreatorController.php" "$BASE/api/modules/v1/controllers/CreatorController.php"
fetch "$TMP/main.php" "$BASE/api/config/main.php"
fetch "$TMP/serve-uploads.php" "$BASE/api/web/serve-uploads.php"
fetch "$TMP/api-index.php" "$GITHUB/deploy/cpanel/entrypoints/api-index.php"
fetch "$TMP/find-reel-video.php" "$BASE/scripts/find-reel-video.php"
fetch "$TMP/ensure-upload-dirs.php" "$BASE/scripts/ensure-upload-dirs.php"
fetch "$TMP/app.js" "$WEB_BASE/js/app.js"
fetch "$TMP/index.html" "$WEB_BASE/index.html"

install() {
  cp -f "$1" "$2"
  chmod u+rw "$2" 2>/dev/null || true
  echo "OK: $2"
}

install "$TMP/DreamlandStorageMode.php" "$DL/common/helpers/DreamlandStorageMode.php"
install "$TMP/DreamlandMediaUrl.php" "$DL/common/helpers/DreamlandMediaUrl.php"
install "$TMP/DreamlandWasabiStorage.php" "$DL/common/helpers/DreamlandWasabiStorage.php"
install "$TMP/FileUpload.php" "$DL/common/components/FileUpload.php"
install "$TMP/MediaController.php" "$DL/api/modules/v1/controllers/MediaController.php"
install "$TMP/HealthController.php" "$DL/api/modules/v1/controllers/HealthController.php"
install "$TMP/CreatorController.php" "$DL/api/modules/v1/controllers/CreatorController.php"
install "$TMP/main.php" "$DL/api/config/main.php"
install "$TMP/serve-uploads.php" "$DL/api/web/serve-uploads.php"
install "$TMP/find-reel-video.php" "$DL/scripts/find-reel-video.php"
install "$TMP/ensure-upload-dirs.php" "$DL/scripts/ensure-upload-dirs.php"
install "$TMP/app.js" "$WEB/js/app.js"
install "$TMP/index.html" "$WEB/index.html"

mkdir -p "$API"
install "$TMP/api-index.php" "$API/index.php"

echo ""
echo "--- Writable upload directories ---"
cd "$DL"
php scripts/ensure-upload-dirs.php

echo ""
echo "--- .env: force local disk storage (trials) ---"
ENV_FILE="$DL/.env"
touch "$ENV_FILE"
set_env() {
  local key="$1" val="$2"
  if grep -q "^${key}=" "$ENV_FILE" 2>/dev/null; then
    sed -i.bak "s|^${key}=.*|${key}=${val}|" "$ENV_FILE"
  else
    echo "${key}=${val}" >> "$ENV_FILE"
  fi
}
set_env "DREAMLAND_STORAGE" "local"
set_env "DREAMLAND_FORCE_LOCAL_UPLOADS" "1"
set_env "DREAMLAND_TRIAL_MODE" "1"
set_env "DREAMLAND_UPLOAD_DIR" "$DL/api/runtime/uploads"
rm -f "$ENV_FILE.bak" 2>/dev/null || true
echo "OK: DREAMLAND_STORAGE=local (switch to wasabi for production)"

echo ""
echo "--- Symlink uploads for direct Apache serve ---"
mkdir -p "$API/frontend/web"
ln -sfn "$DL/api/runtime/uploads" "$API/frontend/web/uploads"
echo "OK: $API/frontend/web/uploads -> $DL/api/runtime/uploads"

rm -rf "$DL/api/runtime/cache/"* 2>/dev/null || true

echo ""
echo "--- Latest reel on disk ---"
php scripts/find-reel-video.php 2>&1 | head -40 || true

echo ""
echo "Done. Re-upload a reel from Studio, then hard-refresh the PWA."
echo "Health: https://dreamlandgh.app/api/v1/health (storage_mode should be local)"
echo "Production later: set DREAMLAND_STORAGE=wasabi and remove DREAMLAND_FORCE_LOCAL_UPLOADS"
