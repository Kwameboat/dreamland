#!/bin/bash
# Fix admin Content Creator view/delete 500/404 — installs PHP files from GitHub.
# Run: curl -fsSL https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-creator-view.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
TMP="/tmp/dreamland-creator-fix-$$"
BASE="$GITHUB/backend/sayhi_v1.6_code"

mkdir -p "$TMP"
trap 'rm -rf "$TMP"' EXIT

echo "=== Fix admin Content Creator view ==="

fetch() { curl -fsSL -o "$1" "$2"; }

fetch "$TMP/ContentCreatorController.php" "$BASE/backend/controllers/ContentCreatorController.php"
fetch "$TMP/view.php" "$BASE/backend/views/content-creator/view.php"
fetch "$TMP/CreatorSearch.php" "$BASE/backend/models/CreatorSearch.php"
fetch "$TMP/DreamlandAudience.php" "$BASE/common/models/DreamlandAudience.php"
fetch "$TMP/DreamlandCreatorApproval.php" "$BASE/common/helpers/DreamlandCreatorApproval.php"
fetch "$TMP/sync-pwa-creators.php" "$BASE/scripts/sync-pwa-creators.php"
fetch "$TMP/check-creator.php" "$BASE/scripts/check-creator.php"

cp -f "$TMP/ContentCreatorController.php" "$DL/backend/controllers/ContentCreatorController.php"
cp -f "$TMP/view.php" "$DL/backend/views/content-creator/view.php"
cp -f "$TMP/CreatorSearch.php" "$DL/backend/models/CreatorSearch.php"
cp -f "$TMP/DreamlandAudience.php" "$DL/common/models/DreamlandAudience.php"
cp -f "$TMP/DreamlandCreatorApproval.php" "$DL/common/helpers/DreamlandCreatorApproval.php"
cp -f "$TMP/sync-pwa-creators.php" "$DL/scripts/sync-pwa-creators.php"
cp -f "$TMP/check-creator.php" "$DL/scripts/check-creator.php"
echo "OK: admin creator PHP files installed."

cd "$DL"
php scripts/apply-dreamland-v2-migration.php 2>&1 | tail -2 || true
php scripts/apply-dreamland-creator-approval-migration.php 2>&1 | tail -3 || true
php scripts/sync-pwa-creators.php 2>&1 || true
echo ""
echo "--- Creators in DB ---"
php scripts/check-creator.php 2>&1 || php scripts/check-creator.php 2>&1 || true

echo ""
echo "Done. Open: https://dreamlandgh.app/admin/index.php?r=content-creator/view&id=2"
