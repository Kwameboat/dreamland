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
fetch "$TMP/app.js" "$WEB_BASE/js/app.js"
fetch "$TMP/config.js" "$WEB_BASE/js/config.js"
fetch "$TMP/index.html" "$WEB_BASE/index.html"

install() {
  cp -f "$1" "$2"
  chmod u+rw "$2" 2>/dev/null || true
  echo "OK: $2"
}

install "$TMP/DreamlandMediaUrl.php" "$DL/common/helpers/DreamlandMediaUrl.php"
install "$TMP/FileUpload.php" "$DL/common/components/FileUpload.php"
install "$TMP/DreamlandWasabiStorage.php" "$DL/common/helpers/DreamlandWasabiStorage.php"
install "$TMP/HealthController.php" "$DL/api/modules/v1/controllers/HealthController.php"
install "$TMP/app.js" "$WEB/js/app.js"
install "$TMP/config.js" "$WEB/js/config.js"
install "$TMP/index.html" "$WEB/index.html"

rm -rf "$DL/api/runtime/cache/"* 2>/dev/null || true

echo ""
echo "Done. Hard-refresh the PWA (Ctrl+Shift+R) and open Watch."
echo "https://dreamlandgh.app"
