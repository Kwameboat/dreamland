#!/bin/bash
# Full Content Creators module repair for cPanel (admin list/view/approve/delete).
# Run: curl -fsSL https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-content-creators.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
TMP="/tmp/dreamland-creators-$$"
BASE="$GITHUB/backend/sayhi_v1.6_code"

mkdir -p "$TMP"
trap 'rm -rf "$TMP"' EXIT

echo "=== Dreamland Content Creators module fix ==="

fetch() { curl -fsSL -o "$1" "$2"; }

fetch "$TMP/ContentCreatorController.php" "$BASE/backend/controllers/ContentCreatorController.php"
fetch "$TMP/CreatorSearch.php" "$BASE/backend/models/CreatorSearch.php"
fetch "$TMP/CreatorForm.php" "$BASE/backend/models/CreatorForm.php"
fetch "$TMP/DreamlandAudience.php" "$BASE/common/models/DreamlandAudience.php"
fetch "$TMP/DreamlandCreatorApproval.php" "$BASE/common/helpers/DreamlandCreatorApproval.php"
fetch "$TMP/index.php" "$BASE/backend/views/content-creator/index.php"
fetch "$TMP/view.php" "$BASE/backend/views/content-creator/view.php"
fetch "$TMP/sync-pwa-creators.php" "$BASE/scripts/sync-pwa-creators.php"
fetch "$TMP/check-creator.php" "$BASE/scripts/check-creator.php"

install() {
  cp -f "$1" "$2"
  chmod u+rw "$2" 2>/dev/null || true
  echo "OK: $2"
}

install "$TMP/ContentCreatorController.php" "$DL/backend/controllers/ContentCreatorController.php"
install "$TMP/CreatorSearch.php" "$DL/backend/models/CreatorSearch.php"
install "$TMP/CreatorForm.php" "$DL/backend/models/CreatorForm.php"
install "$TMP/DreamlandAudience.php" "$DL/common/models/DreamlandAudience.php"
install "$TMP/DreamlandCreatorApproval.php" "$DL/common/helpers/DreamlandCreatorApproval.php"
install "$TMP/index.php" "$DL/backend/views/content-creator/index.php"
install "$TMP/view.php" "$DL/backend/views/content-creator/view.php"
install "$TMP/sync-pwa-creators.php" "$DL/scripts/sync-pwa-creators.php"
install "$TMP/check-creator.php" "$DL/scripts/check-creator.php"

echo ""
echo "--- DB migrations (idempotent) ---"
cd "$DL"
php scripts/apply-dreamland-v2-migration.php 2>&1 | tail -2 || true
php scripts/apply-dreamland-creator-approval-migration.php 2>&1 | tail -3 || true
php scripts/sync-pwa-creators.php 2>&1 || true

echo ""
echo "--- Clear Yii schema cache ---"
rm -rf "$DL/backend/runtime/cache/"* 2>/dev/null || true
rm -rf "$DL/api/runtime/cache/"* 2>/dev/null || true
rm -rf "$DL/common/runtime/cache/"* 2>/dev/null || true
echo "Schema cache cleared."

echo ""
echo "--- Creators in database ---"
php scripts/check-creator.php 2>&1 || true

echo ""
echo "Done."
echo "Admin: https://dreamlandgh.app/admin/index.php?r=content-creator/index"
echo "Use the User ID column (not row #) when opening view/delete links."
