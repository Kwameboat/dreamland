#!/bin/bash
# Push PWA updates to installed users (network-first SW + auto refresh).
# Run: curl -fsSL -A "DreamlandDeploy/1.0" https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-pwa-updates.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
WEB="$HOME_DIR/public_html"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"

TMP="/tmp/dreamland-pwa-upd-$$"
mkdir -p "$TMP"
trap 'rm -rf "$TMP"' EXIT

fetch() { curl -fsSL -A "DreamlandDeploy/1.0" -o "$1" "$2"; }

fetch "$TMP/index.html" "$GITHUB/web/index.html"
fetch "$TMP/app.js" "$GITHUB/web/js/app.js"
fetch "$TMP/sw.js" "$GITHUB/web/sw.js"
fetch "$TMP/build-version.json" "$GITHUB/web/build-version.json"

install() { cp -f "$1" "$2"; chmod u+rw "$2" 2>/dev/null || true; echo "OK: $2"; }

if [ -d "$WEB" ]; then
  install "$TMP/index.html" "$WEB/index.html"
  install "$TMP/app.js" "$WEB/js/app.js"
  install "$TMP/sw.js" "$WEB/sw.js"
  install "$TMP/build-version.json" "$WEB/build-version.json"
fi

BUILD="$(grep -o 'build-[0-9]*' "$TMP/build-version.json" | head -1 || echo unknown)"
echo ""
echo "Done ($BUILD). Installed PWAs auto-update on next open or within ~5 minutes."
