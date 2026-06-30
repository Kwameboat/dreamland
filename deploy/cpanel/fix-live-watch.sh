#!/bin/bash
# Fix live viewer connection + stop reels playing behind live broadcast.
# Run: curl -fsSL -A "DreamlandDeploy/1.0" https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-live-watch.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
WEB="$HOME_DIR/public_html"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
TMP="/tmp/dreamland-live-watch-$$"
mkdir -p "$TMP"
trap 'rm -rf "$TMP"' EXIT

fetch() { curl -fsSL -A "DreamlandDeploy/1.0" -o "$1" "$2"; }
install() { cp -f "$1" "$2"; chmod u+rw "$2" 2>/dev/null || true; echo "OK: $2"; }

PWA_JS=(app.js dreamland-live.js)
for js in "${PWA_JS[@]}"; do fetch "$TMP/$js" "$GITHUB/web/js/$js"; done
fetch "$TMP/app.css" "$GITHUB/web/css/app.css"
fetch "$TMP/build-version.json" "$GITHUB/web/build-version.json"
fetch "$TMP/sw.js" "$GITHUB/web/sw.js"

if [ -d "$WEB" ]; then
  mkdir -p "$WEB/js" "$WEB/css"
  for js in "${PWA_JS[@]}"; do install "$TMP/$js" "$WEB/js/$js"; done
  install "$TMP/app.css" "$WEB/css/app.css"
  install "$TMP/build-version.json" "$WEB/build-version.json"
  install "$TMP/sw.js" "$WEB/sw.js"
fi

BUILD="$(grep -o 'build-[0-9]*' "$TMP/build-version.json" | head -1 || echo unknown)"
echo ""
echo "Done ($BUILD). Redeploy live-server on Render for host reconnect fix."
echo "Test: creator goes live, viewer joins — video should load within a few seconds."
