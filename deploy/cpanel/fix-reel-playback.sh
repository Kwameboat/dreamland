#!/bin/bash
# Fix PWA reel video URLs + autoplay on cPanel (local uploads under /api/...).
# Run: curl -fsSL https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-reel-playback.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
WEB="${DREAMLAND_WEB_DIR:-$HOME_DIR/public_html}"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
TMP="/tmp/dreamland-playback-$$"
BASE="$GITHUB/backend/sayhi_v1.6_code"
WEB_BASE="$GITHUB/web"

mkdir -p "$TMP"
trap 'rm -rf "$TMP"' EXIT

echo "=== Dreamland reel playback fix ==="

fetch() { curl -fsSL -o "$1" "$2"; }

fetch "$TMP/DreamlandMediaUrl.php" "$BASE/common/helpers/DreamlandMediaUrl.php"
fetch "$TMP/FileUpload.php" "$BASE/common/components/FileUpload.php"
fetch "$TMP/DreamlandWasabiStorage.php" "$BASE/common/helpers/DreamlandWasabiStorage.php"
fetch "$TMP/HealthController.php" "$BASE/api/modules/v1/controllers/HealthController.php"
fetch "$TMP/Post.php" "$BASE/api/modules/v1/models/Post.php"
fetch "$TMP/PostSearch.php" "$BASE/api/modules/v1/models/PostSearch.php"
fetch "$TMP/CreatorController.php" "$BASE/api/modules/v1/controllers/CreatorController.php"
fetch "$TMP/repair-reel-gallery.php" "$BASE/scripts/repair-reel-gallery.php"
fetch "$TMP/find-reel-video.php" "$BASE/scripts/find-reel-video.php"
fetch "$TMP/serve-uploads.php" "$BASE/api/web/serve-uploads.php"
fetch "$TMP/index.php" "$BASE/api/web/index.php"
fetch "$TMP/app.js" "$WEB_BASE/js/app.js"
fetch "$TMP/config.js" "$WEB_BASE/js/config.js"
fetch "$TMP/index.html" "$WEB_BASE/index.html"
fetch "$TMP/app.css" "$WEB_BASE/css/app.css"

install() {
  cp -f "$1" "$2"
  chmod u+rw "$2" 2>/dev/null || true
  echo "OK: $2"
}

install "$TMP/DreamlandMediaUrl.php" "$DL/common/helpers/DreamlandMediaUrl.php"
install "$TMP/FileUpload.php" "$DL/common/components/FileUpload.php"
install "$TMP/DreamlandWasabiStorage.php" "$DL/common/helpers/DreamlandWasabiStorage.php"
install "$TMP/HealthController.php" "$DL/api/modules/v1/controllers/HealthController.php"
install "$TMP/Post.php" "$DL/api/modules/v1/models/Post.php"
install "$TMP/PostSearch.php" "$DL/api/modules/v1/models/PostSearch.php"
install "$TMP/CreatorController.php" "$DL/api/modules/v1/controllers/CreatorController.php"
install "$TMP/repair-reel-gallery.php" "$DL/scripts/repair-reel-gallery.php"
install "$TMP/find-reel-video.php" "$DL/scripts/find-reel-video.php"
install "$TMP/serve-uploads.php" "$DL/api/web/serve-uploads.php"
install "$TMP/index.php" "$DL/api/web/index.php"
install "$TMP/app.js" "$WEB/js/app.js"
install "$TMP/config.js" "$WEB/js/config.js"
install "$TMP/index.html" "$WEB/index.html"
install "$TMP/app.css" "$WEB/css/app.css"

rm -rf "$DL/api/runtime/cache/"* 2>/dev/null || true

echo ""
echo "--- Locate reel video files ---"
php scripts/find-reel-video.php 1 2>&1 || true

echo ""
echo "--- Repair reel gallery rows ---"
cd "$DL"
php scripts/repair-reel-gallery.php 1 2>&1 || true

echo ""
echo "Done. Hard-refresh the PWA (Ctrl+Shift+R) and open Watch."
echo "https://dreamlandgh.app"
