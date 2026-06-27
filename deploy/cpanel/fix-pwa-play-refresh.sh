#!/bin/bash
# Deploy PWA pause/play + pull-to-refresh (build-178243+).
# Run after pushing to GitHub:
#   curl -fsSL https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-pwa-play-refresh.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
WEB="${DREAMLAND_WEB_DIR:-$HOME_DIR/public_html}"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
WEB_BASE="$GITHUB/web"
UA="DreamlandDeploy/1.0"
TMP="/tmp/dreamland-pwa-refresh-$$"

mkdir -p "$TMP" "$WEB/js" "$WEB/css"
trap 'rm -rf "$TMP"' EXIT

fetch() {
  local dest="$1" url="$2"
  if ! curl -fsSL -A "$UA" -o "$dest" "$url"; then
    echo "FAIL: $url" >&2
    echo "Tip: push latest dreamland repo to GitHub main, then retry." >&2
    exit 1
  fi
  echo "OK: fetched $(basename "$dest")"
}

echo "=== Dreamland PWA play/pause + pull refresh ==="

fetch "$TMP/app.js" "$WEB_BASE/js/app.js"
fetch "$TMP/dreamland-social.js" "$WEB_BASE/js/dreamland-social.js"
fetch "$TMP/index.html" "$WEB_BASE/index.html"
fetch "$TMP/app.css" "$WEB_BASE/css/app.css"
fetch "$TMP/sw.js" "$WEB_BASE/sw.js"
fetch "$TMP/build-version.json" "$WEB_BASE/build-version.json"

install() {
  cp -f "$1" "$2"
  chmod u+rw "$2" 2>/dev/null || true
  echo "Installed: $2"
}

install "$TMP/app.js" "$WEB/js/app.js"
install "$TMP/dreamland-social.js" "$WEB/js/dreamland-social.js"
install "$TMP/index.html" "$WEB/index.html"
install "$TMP/app.css" "$WEB/css/app.css"
install "$TMP/sw.js" "$WEB/sw.js"
install "$TMP/build-version.json" "$WEB/build-version.json"

echo ""
echo "Build: $(grep -o 'build-[0-9]*' "$WEB/build-version.json" | head -1 || echo unknown)"
echo "Done. Hard-refresh PWA: Ctrl+Shift+R"
echo "https://dreamlandgh.app"
