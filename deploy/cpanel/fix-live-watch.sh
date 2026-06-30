#!/bin/bash
# Fix live viewer stuck on "Connecting" — self-host libs + room re-register.
# Run: curl -fsSL -A "DreamlandDeploy/1.0" https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-live-watch.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
WEB="$HOME_DIR/public_html"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
BASE="$GITHUB/backend/sayhi_v1.6_code"
TMP="/tmp/dreamland-live-watch-$$"
mkdir -p "$TMP"
trap 'rm -rf "$TMP"' EXIT

fetch() { curl -fsSL -A "DreamlandDeploy/1.0" -o "$1" "$2"; }
install() { cp -f "$1" "$2"; chmod u+rw "$2" 2>/dev/null || true; echo "OK: $2"; }

echo "=== Dreamland live viewer fix ==="

fetch "$TMP/app.js" "$GITHUB/web/js/app.js"
fetch "$TMP/dreamland-live.js" "$GITHUB/web/js/dreamland-live.js"
fetch "$TMP/app.css" "$GITHUB/web/css/app.css"
fetch "$TMP/build-version.json" "$GITHUB/web/build-version.json"
fetch "$TMP/sw.js" "$GITHUB/web/sw.js"
fetch "$TMP/LiveController.php" "$BASE/api/modules/v1/controllers/LiveController.php"

mkdir -p "$TMP/vendor"
fetch "$TMP/vendor/socket.io.esm.min.js" "https://cdn.socket.io/4.8.1/socket.io.esm.min.js"
fetch "$TMP/vendor/mediasoup-client.esm.js" "https://esm.sh/mediasoup-client@3.7.17?bundle"

install "$TMP/LiveController.php" "$DL/api/modules/v1/controllers/LiveController.php"

if [ -d "$WEB" ]; then
  mkdir -p "$WEB/js/vendor" "$WEB/js" "$WEB/css"
  install "$TMP/app.js" "$WEB/js/app.js"
  install "$TMP/dreamland-live.js" "$WEB/js/dreamland-live.js"
  install "$TMP/app.css" "$WEB/css/app.css"
  install "$TMP/build-version.json" "$WEB/build-version.json"
  install "$TMP/sw.js" "$WEB/sw.js"
  install "$TMP/vendor/socket.io.esm.min.js" "$WEB/js/vendor/socket.io.esm.min.js"
  install "$TMP/vendor/mediasoup-client.esm.js" "$WEB/js/vendor/mediasoup-client.esm.js"
fi

rm -rf "$DL/api/runtime/cache/"* 2>/dev/null || true

BUILD="$(grep -o 'build-[0-9]*' "$TMP/build-version.json" | head -1 || echo unknown)"
echo ""
echo "Done ($BUILD). Open Live tab, wait for status messages, then watch."
echo "Creator must tap Go live and keep the broadcast screen open."
