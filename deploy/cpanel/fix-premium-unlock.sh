#!/bin/bash
# Fix premium unlock: feed respects purchases after credit unlock.
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

echo "=== Dreamland premium unlock fix (build-178246) ==="

fetch() { curl -fsSL -A "DreamlandDeploy/1.0" -o "$1" "$2"; }

fetch "$TMP/PostController.php" "$BASE/api/modules/v1/controllers/PostController.php"
fetch "$TMP/app.js" "$GITHUB/web/js/app.js"
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
  install "$TMP/app.js" "$WEB/js/app.js"
  install "$TMP/index.html" "$WEB/index.html"
  install "$TMP/build-version.json" "$WEB/build-version.json"
  install "$TMP/sw.js" "$WEB/sw.js"
fi

rm -rf "$DL/api/runtime/cache/"* 2>/dev/null || true

echo ""
echo "Done. Unlocked premium reels now play full length permanently for purchasers."
