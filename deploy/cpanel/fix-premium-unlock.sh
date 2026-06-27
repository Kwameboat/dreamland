#!/bin/bash
# Fix premium unlock + deploy full PWA JS bundle (includes dreamland-reels-fast.js).
# Run: curl -fsSL -A "DreamlandDeploy/1.0" https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-premium-unlock.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
WEB="$HOME_DIR/public_html"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
BASE="$GITHUB/backend/sayhi_v1.6_code"
TMP="/tmp/dreamland-unlock-$$"

mkdir -p "$TMP"
trap 'rm -rf "$TMP"' EXIT

echo "=== Dreamland premium unlock + PWA boot fix ==="

fetch() { curl -fsSL -A "DreamlandDeploy/1.0" -o "$1" "$2"; }

fetch "$TMP/PostController.php" "$BASE/api/modules/v1/controllers/PostController.php"

PWA_JS=(
  app.js
  config.js
  dreamland-features.js
  dreamland-ai.js
  dreamland-social.js
  dreamland-profile.js
  dreamland-search.js
  dreamland-account.js
  dreamland-reels-fast.js
  dreamland-live.js
)

for js in "${PWA_JS[@]}"; do
  fetch "$TMP/$js" "$GITHUB/web/js/$js"
done

fetch "$TMP/index.html" "$GITHUB/web/index.html"
fetch "$TMP/build-version.json" "$GITHUB/web/build-version.json"
fetch "$TMP/sw.js" "$GITHUB/web/sw.js"

install() {
  cp -f "$1" "$2"
  chmod u+rw "$2" 2>/dev/null || true
  echo "OK: $2"
}

install "$TMP/PostController.php" "$DL/api/modules/v1/controllers/PostController.php"

if [ -d "$WEB" ]; then
  mkdir -p "$WEB/js"
  for js in "${PWA_JS[@]}"; do
    install "$TMP/$js" "$WEB/js/$js"
  done
  install "$TMP/index.html" "$WEB/index.html"
  install "$TMP/build-version.json" "$WEB/build-version.json"
  install "$TMP/sw.js" "$WEB/sw.js"
fi

rm -rf "$DL/api/runtime/cache/"* 2>/dev/null || true

BUILD="$(grep -o 'build-[0-9]*' "$TMP/build-version.json" | head -1 || echo unknown)"
echo ""
echo "Done ($BUILD). Hard refresh (Ctrl+Shift+R) after deploy."
