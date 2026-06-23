#!/bin/bash
# Faster reel uploads: skip blocking Sightengine/Rekognition + getID3 probe; PWA upload progress.
# Run: curl -fsSL https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-reel-upload.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
WEB="$HOME_DIR/public_html"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
TMP="/tmp/dreamland-upload-fix-$$"
BASE="$GITHUB/backend/sayhi_v1.6_code"

mkdir -p "$TMP"
trap 'rm -rf "$TMP"' EXIT

echo "=== Dreamland faster reel upload fix ==="

fetch() { curl -fsSL -o "$1" "$2"; }

fetch "$TMP/CreatorController.php" "$BASE/api/modules/v1/controllers/CreatorController.php"
fetch "$TMP/FileUpload.php" "$BASE/common/components/FileUpload.php"
fetch "$TMP/DreamlandUploadLimits.php" "$BASE/common/helpers/DreamlandUploadLimits.php"
fetch "$TMP/app.js" "$GITHUB/web/js/app.js"
fetch "$TMP/index.html" "$GITHUB/web/index.html"
fetch "$TMP/build-version.json" "$GITHUB/web/build-version.json"

cp -f "$TMP/CreatorController.php" "$DL/api/modules/v1/controllers/CreatorController.php"
cp -f "$TMP/FileUpload.php" "$DL/common/components/FileUpload.php"
cp -f "$TMP/DreamlandUploadLimits.php" "$DL/common/helpers/DreamlandUploadLimits.php"
echo "OK: API upload handlers"

mkdir -p "$WEB/js"
cp -f "$TMP/app.js" "$WEB/js/app.js"
cp -f "$TMP/index.html" "$WEB/index.html"
cp -f "$TMP/build-version.json" "$WEB/build-version.json"
echo "OK: PWA studio upload UI"

rm -rf "$DL/api/runtime/cache/"* 2>/dev/null || true
echo "API schema cache cleared."

echo ""
echo "Done. Hard-refresh the PWA (Ctrl+Shift+R) and try uploading again."
echo "You should see upload % progress; server no longer blocks on legacy video moderation."
