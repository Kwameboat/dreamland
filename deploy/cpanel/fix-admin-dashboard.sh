#!/bin/bash
# Deploy admin dashboard refresh toolbar + hero actions on cPanel.
# Run: curl -fsSL https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-admin-dashboard.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
ADMIN_WEB="${DREAMLAND_ADMIN_WEB_DIR:-$HOME_DIR/public_html/admin}"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
BASE="$GITHUB/backend/sayhi_v1.6_code"
TMP="/tmp/dreamland-admin-dash-$$"

mkdir -p "$TMP"
trap 'rm -rf "$TMP"' EXIT

echo "=== Dreamland admin dashboard refresh fix ==="

fetch() { curl -fsSL -o "$1" "$2"; }

fetch "$TMP/content.php" "$BASE/backend/views/layouts/content.php"
fetch "$TMP/index.php" "$BASE/backend/views/site/index.php"
fetch "$TMP/SiteController.php" "$BASE/backend/controllers/SiteController.php"
fetch "$TMP/dreamland-admin.css" "$BASE/backend/web/css/dreamland-admin.css"

install() {
  cp -f "$1" "$2"
  chmod u+rw "$2" 2>/dev/null || true
  echo "OK: $2"
}

install "$TMP/content.php" "$DL/backend/views/layouts/content.php"
install "$TMP/index.php" "$DL/backend/views/site/index.php"
install "$TMP/SiteController.php" "$DL/backend/controllers/SiteController.php"
install "$TMP/dreamland-admin.css" "$DL/backend/web/css/dreamland-admin.css"

ADMIN_WEB="${DREAMLAND_ADMIN_WEB_DIR:-$HOME_DIR/public_html/admin}"
if [ -d "$ADMIN_WEB/web/css" ]; then
  install "$TMP/dreamland-admin.css" "$ADMIN_WEB/web/css/dreamland-admin.css"
elif [ -d "$ADMIN_WEB/css" ]; then
  install "$TMP/dreamland-admin.css" "$ADMIN_WEB/css/dreamland-admin.css"
fi

rm -rf "$DL/backend/runtime/cache/"* 2>/dev/null || true

echo ""
echo "Done. Hard-refresh the admin dashboard (Ctrl+Shift+R)."
echo "You should see Refresh dashboard + Update system in the page header."
