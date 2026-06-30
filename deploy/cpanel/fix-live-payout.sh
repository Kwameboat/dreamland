#!/bin/bash
# Fix live/reel overlap, creator payouts, admin revenue dashboard.
# Run: curl -fsSL -A "DreamlandDeploy/1.0" https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-live-payout.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
WEB="$HOME_DIR/public_html"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
BASE="$GITHUB/backend/sayhi_v1.6_code"
TMP="/tmp/dreamland-live-payout-$$"

mkdir -p "$TMP"
trap 'rm -rf "$TMP"' EXIT

echo "=== Dreamland live + payout fix ==="

fetch() { curl -fsSL -A "DreamlandDeploy/1.0" -o "$1" "$2"; }

install() {
  cp -f "$1" "$2"
  chmod u+rw "$2" 2>/dev/null || true
  echo "OK: $2"
}

PWA_JS=(app.js config.js dreamland-features.js dreamland-ai.js dreamland-social.js dreamland-profile.js dreamland-search.js dreamland-account.js dreamland-reels-fast.js dreamland-live.js)
for js in "${PWA_JS[@]}"; do fetch "$TMP/$js" "$GITHUB/web/js/$js"; done
fetch "$TMP/index.html" "$GITHUB/web/index.html"
fetch "$TMP/app.css" "$GITHUB/web/css/app.css"
fetch "$TMP/build-version.json" "$GITHUB/web/build-version.json"
fetch "$TMP/sw.js" "$GITHUB/web/sw.js"

fetch "$TMP/CreatorController.php" "$BASE/api/modules/v1/controllers/CreatorController.php"
fetch "$TMP/PaymentController.php" "$BASE/api/modules/v1/controllers/PaymentController.php"
fetch "$TMP/DreamlandMetaController.php" "$BASE/api/modules/v1/controllers/DreamlandMetaController.php"
fetch "$TMP/DreamlandRevenueController.php" "$BASE/backend/controllers/DreamlandRevenueController.php"
fetch "$TMP/dreamland-revenue-index.php" "$BASE/backend/views/dreamland-revenue/index.php"
fetch "$TMP/left.php" "$BASE/backend/views/layouts/left.php"

install "$TMP/CreatorController.php" "$DL/api/modules/v1/controllers/CreatorController.php"
install "$TMP/PaymentController.php" "$DL/api/modules/v1/controllers/PaymentController.php"
install "$TMP/DreamlandMetaController.php" "$DL/api/modules/v1/controllers/DreamlandMetaController.php"
install "$TMP/DreamlandRevenueController.php" "$DL/backend/controllers/DreamlandRevenueController.php"
mkdir -p "$DL/backend/views/dreamland-revenue"
install "$TMP/dreamland-revenue-index.php" "$DL/backend/views/dreamland-revenue/index.php"
install "$TMP/left.php" "$DL/backend/views/layouts/left.php"

if [ -d "$WEB" ]; then
  mkdir -p "$WEB/js" "$WEB/css"
  for js in "${PWA_JS[@]}"; do install "$TMP/$js" "$WEB/js/$js"; done
  install "$TMP/index.html" "$WEB/index.html"
  install "$TMP/app.css" "$WEB/css/app.css"
  install "$TMP/build-version.json" "$WEB/build-version.json"
  install "$TMP/sw.js" "$WEB/sw.js"
fi

rm -rf "$DL/api/runtime/cache/"* "$DL/backend/runtime/cache/"* 2>/dev/null || true

BUILD="$(grep -o 'build-[0-9]*' "$TMP/build-version.json" | head -1 || echo unknown)"
echo ""
echo "Done ($BUILD). Hard refresh PWA. Admin revenue: /admin/dreamland-revenue"
