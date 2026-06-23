#!/bin/bash
# Fix PWA signup: false "email already exist" + creator pending approval.
# Safe to re-run (idempotent migrations, backs up app.js first).
#
# Run on cPanel:
#   curl -fsSL https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/hotfix-pwa-registration.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
WEB="$HOME_DIR/public_html"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
TMP="/tmp/dreamland-reg-fix-$$"

echo "=== Dreamland PWA registration fix ==="

if [ ! -d "$DL/api" ] || [ ! -d "$DL/scripts" ]; then
  echo "FAIL: Dreamland app not found at $DL"
  exit 1
fi
if [ ! -d "$WEB/js" ]; then
  echo "FAIL: PWA not found at $WEB/js"
  exit 1
fi

mkdir -p "$TMP"
trap 'rm -rf "$TMP"' EXIT

echo "Downloading patched files..."
curl -fsSL -o "$TMP/DreamlandAuthController.php" "$GITHUB/backend/sayhi_v1.6_code/api/modules/v1/controllers/DreamlandAuthController.php"
curl -fsSL -o "$TMP/User.php" "$GITHUB/backend/sayhi_v1.6_code/api/modules/v1/models/User.php"
curl -fsSL -o "$TMP/CreatorController.php" "$GITHUB/backend/sayhi_v1.6_code/api/modules/v1/controllers/CreatorController.php"
curl -fsSL -o "$TMP/DreamlandCreatorApproval.php" "$GITHUB/backend/sayhi_v1.6_code/common/helpers/DreamlandCreatorApproval.php"
curl -fsSL -o "$TMP/app.js" "$GITHUB/web/js/app.js"
curl -fsSL -o "$TMP/index.html" "$GITHUB/web/index.html"
curl -fsSL -o "$TMP/env-config.js" "$GITHUB/web/env-config.js"
curl -fsSL -o "$TMP/sw.js" "$GITHUB/web/sw.js"

STAMP="$(date +%Y%m%d-%H%M%S)"
cp -f "$WEB/js/app.js" "$WEB/js/app.js.bak-$STAMP" 2>/dev/null || true

cp -f "$TMP/DreamlandAuthController.php" "$DL/api/modules/v1/controllers/DreamlandAuthController.php"
cp -f "$TMP/User.php" "$DL/api/modules/v1/models/User.php"
cp -f "$TMP/CreatorController.php" "$DL/api/modules/v1/controllers/CreatorController.php"
cp -f "$TMP/DreamlandCreatorApproval.php" "$DL/common/helpers/DreamlandCreatorApproval.php"
cp -f "$TMP/app.js" "$WEB/js/app.js"
cp -f "$TMP/index.html" "$WEB/index.html"
cp -f "$TMP/env-config.js" "$WEB/env-config.js"
cp -f "$TMP/sw.js" "$WEB/sw.js"
chmod u+rw "$DL/api/modules/v1/controllers/DreamlandAuthController.php" "$DL/api/modules/v1/models/User.php" \
  "$DL/api/modules/v1/controllers/CreatorController.php" "$DL/common/helpers/DreamlandCreatorApproval.php" \
  "$WEB/js/app.js" "$WEB/index.html" "$WEB/env-config.js" "$WEB/sw.js" 2>/dev/null || true
echo "OK: API + PWA files installed (app.js backed up to app.js.bak-$STAMP)."

echo ""
echo "--- Dreamland DB columns (creator approval, idempotent) ---"
cd "$DL"
php scripts/apply-dreamland-v2-migration.php 2>&1 | tail -3 || true
php scripts/apply-dreamland-creator-approval-migration.php 2>&1 | tail -5 || true

echo ""
echo "--- Verify ---"
curl -sS -o /dev/null -w "api health: HTTP %{http_code}\n" "https://dreamlandgh.app/api/v1/health" || true

echo ""
echo "Done."
echo "  PWA signup: https://dreamlandgh.app"
echo "  Admin approve creators: https://dreamlandgh.app/admin/index.php?r=content-creator/index&filter=pending"
echo "  Hard-refresh the PWA (Ctrl+Shift+R) after deploy."
