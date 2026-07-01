#!/bin/bash
# Fix live viewer "xhr poll error" — same-origin Socket.IO proxy + hardened client.
# Run: curl -fsSL -A "DreamlandDeploy/1.0" https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-live-signal.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
WEB="$HOME_DIR/public_html"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
BASE="$GITHUB/backend/sayhi_v1.6_code"
TMP="/tmp/dreamland-live-signal-$$"
mkdir -p "$TMP"
trap 'rm -rf "$TMP"' EXIT

fetch() { curl -fsSL -A "DreamlandDeploy/1.0" -o "$1" "$2"; }
install() { cp -f "$1" "$2"; chmod u+rw "$2" 2>/dev/null || true; echo "OK: $2"; }

echo "=== Dreamland live signaling fix (xhr poll error) ==="

fetch "$TMP/dreamland-live.js" "$GITHUB/web/js/dreamland-live.js"
fetch "$TMP/app.js" "$GITHUB/web/js/app.js"
fetch "$TMP/.htaccess" "$GITHUB/web/.htaccess"
fetch "$TMP/live-socket-index.php" "$GITHUB/web/live-socket/index.php"
fetch "$TMP/live-socket-htaccess" "$GITHUB/web/live-socket/.htaccess"
fetch "$TMP/DreamlandLiveRtcService.php" "$BASE/common/components/DreamlandLiveRtcService.php"
fetch "$TMP/HealthController.php" "$BASE/api/modules/v1/controllers/HealthController.php"
fetch "$TMP/DreamlandMetaController.php" "$BASE/api/modules/v1/controllers/DreamlandMetaController.php"
fetch "$TMP/LiveController.php" "$BASE/api/modules/v1/controllers/LiveController.php"
fetch "$TMP/build-version.json" "$GITHUB/web/build-version.json"

install "$TMP/DreamlandLiveRtcService.php" "$DL/common/components/DreamlandLiveRtcService.php"
install "$TMP/HealthController.php" "$DL/api/modules/v1/controllers/HealthController.php"
install "$TMP/DreamlandMetaController.php" "$DL/api/modules/v1/controllers/DreamlandMetaController.php"
install "$TMP/LiveController.php" "$DL/api/modules/v1/controllers/LiveController.php"

if [ -d "$WEB" ]; then
  mkdir -p "$WEB/js" "$WEB/live-socket"
  install "$TMP/dreamland-live.js" "$WEB/js/dreamland-live.js"
  install "$TMP/app.js" "$WEB/js/app.js"
  install "$TMP/.htaccess" "$WEB/.htaccess"
  install "$TMP/live-socket-index.php" "$WEB/live-socket/index.php"
  install "$TMP/live-socket-htaccess" "$WEB/live-socket/.htaccess"
  install "$TMP/build-version.json" "$WEB/build-version.json"
fi

rm -rf "$DL/api/runtime/cache/"* 2>/dev/null || true

BUILD="$(grep -o 'build-[0-9]*' "$TMP/build-version.json" | head -1 || echo unknown)"
echo ""
echo "Testing same-origin live proxy..."
curl -fsSL -A "DreamlandDeploy/1.0" "https://dreamlandgh.app/live-socket/health" | head -c 120 || echo "WARN: proxy /health not reachable yet"
echo ""
echo "Done ($BUILD). Hard-refresh PWA (Ctrl+Shift+R), then watch live again."
echo "Also redeploy Render live-server from GitHub for CORS update."
