#!/bin/bash
# Reel upload fix: PHP limits, skip blocking moderation, duration limits, admin minutes setting.
# Run: curl -fsSL https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-reel-upload.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
WEB="$HOME_DIR/public_html"
API="$WEB/api"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
TMP="/tmp/dreamland-upload-fix-$$"
BASE="$GITHUB/backend/sayhi_v1.6_code"

mkdir -p "$TMP"
trap 'rm -rf "$TMP"' EXIT

echo "=== Dreamland reel upload + duration limits fix ==="

fetch() { curl -fsSL -o "$1" "$2"; }

fetch "$TMP/CreatorController.php" "$BASE/api/modules/v1/controllers/CreatorController.php"
fetch "$TMP/FileUpload.php" "$BASE/common/components/FileUpload.php"
fetch "$TMP/DreamlandUploadLimits.php" "$BASE/common/helpers/DreamlandUploadLimits.php"
fetch "$TMP/DreamlandSetting.php" "$BASE/common/models/DreamlandSetting.php"
fetch "$TMP/dreamland-settings-index.php" "$BASE/backend/views/dreamland-settings/index.php"
fetch "$TMP/api-user.ini" "$GITHUB/deploy/cpanel/entrypoints/api-user.ini"
fetch "$TMP/app.js" "$GITHUB/web/js/app.js"
fetch "$TMP/dreamland-features.js" "$GITHUB/web/js/dreamland-features.js"
fetch "$TMP/index.html" "$GITHUB/web/index.html"
fetch "$TMP/build-version.json" "$GITHUB/web/build-version.json"

cp -f "$TMP/CreatorController.php" "$DL/api/modules/v1/controllers/CreatorController.php"
cp -f "$TMP/FileUpload.php" "$DL/common/components/FileUpload.php"
cp -f "$TMP/DreamlandUploadLimits.php" "$DL/common/helpers/DreamlandUploadLimits.php"
cp -f "$TMP/DreamlandSetting.php" "$DL/common/models/DreamlandSetting.php"
cp -f "$TMP/dreamland-settings-index.php" "$DL/backend/views/dreamland-settings/index.php"
echo "OK: API + admin settings"

mkdir -p "$WEB/js" "$API"
cp -f "$TMP/app.js" "$WEB/js/app.js"
cp -f "$TMP/dreamland-features.js" "$WEB/js/dreamland-features.js"
cp -f "$TMP/index.html" "$WEB/index.html"
cp -f "$TMP/build-version.json" "$WEB/build-version.json"
cp -f "$TMP/api-user.ini" "$API/.user.ini"
echo "OK: PWA + API PHP upload limits (.user.ini)"

rm -rf "$DL/api/runtime/cache/"* 2>/dev/null || true

echo ""
echo "PHP upload limits for API:"
php -r "echo 'upload_max_filesize=' . ini_get('upload_max_filesize') . PHP_EOL;" 2>/dev/null || true
echo ""
echo "Done."
echo "1. Hard-refresh PWA (Ctrl+Shift+R)"
echo "2. Admin → Dreamland Settings → set Max reel length (minutes)"
echo "3. Try upload again — you should see Uploading… X% progress"
