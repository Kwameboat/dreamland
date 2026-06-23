#!/bin/bash
# Fix creator pending tab + safety queue → publish workflow on cPanel.
# Run: curl -fsSL https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-content-publish-workflow.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
TMP="/tmp/dreamland-publish-$$"
BASE="$GITHUB/backend/sayhi_v1.6_code"

mkdir -p "$TMP"
trap 'rm -rf "$TMP"' EXIT

echo "=== Dreamland content publish workflow fix ==="

fetch() { curl -fsSL -o "$1" "$2"; }

fetch "$TMP/CreatorSearch.php" "$BASE/backend/models/CreatorSearch.php"
fetch "$TMP/DreamlandAudience.php" "$BASE/common/models/DreamlandAudience.php"
fetch "$TMP/DreamlandCreatorApproval.php" "$BASE/common/helpers/DreamlandCreatorApproval.php"
fetch "$TMP/DreamlandSafetyPipeline.php" "$BASE/common/components/DreamlandSafetyPipeline.php"
fetch "$TMP/DreamlandSafetyController.php" "$BASE/backend/controllers/DreamlandSafetyController.php"
fetch "$TMP/safety-index.php" "$BASE/backend/views/dreamland-safety/index.php"
fetch "$TMP/CreatorController.php" "$BASE/api/modules/v1/controllers/CreatorController.php"
fetch "$TMP/PostController.php" "$BASE/api/modules/v1/controllers/PostController.php"
fetch "$TMP/PostGallary.php" "$BASE/api/modules/v1/models/PostGallary.php"
fetch "$TMP/main.php" "$BASE/common/config/main.php"
fetch "$TMP/process-safety-queue.php" "$BASE/scripts/process-safety-queue.php"
fetch "$TMP/sync-pwa-creators.php" "$BASE/scripts/sync-pwa-creators.php"
fetch "$TMP/check-creator.php" "$BASE/scripts/check-creator.php"

install() {
  cp -f "$1" "$2"
  chmod u+rw "$2" 2>/dev/null || true
  echo "OK: $2"
}

install "$TMP/CreatorSearch.php" "$DL/backend/models/CreatorSearch.php"
install "$TMP/DreamlandAudience.php" "$DL/common/models/DreamlandAudience.php"
install "$TMP/DreamlandCreatorApproval.php" "$DL/common/helpers/DreamlandCreatorApproval.php"
install "$TMP/DreamlandSafetyPipeline.php" "$DL/common/components/DreamlandSafetyPipeline.php"
install "$TMP/DreamlandSafetyController.php" "$DL/backend/controllers/DreamlandSafetyController.php"
install "$TMP/safety-index.php" "$DL/backend/views/dreamland-safety/index.php"
install "$TMP/CreatorController.php" "$DL/api/modules/v1/controllers/CreatorController.php"
install "$TMP/PostController.php" "$DL/api/modules/v1/controllers/PostController.php"
install "$TMP/PostGallary.php" "$DL/api/modules/v1/models/PostGallary.php"
install "$TMP/main.php" "$DL/common/config/main.php"
install "$TMP/process-safety-queue.php" "$DL/scripts/process-safety-queue.php"
install "$TMP/sync-pwa-creators.php" "$DL/scripts/sync-pwa-creators.php"
install "$TMP/check-creator.php" "$DL/scripts/check-creator.php"

echo ""
echo "--- DB migrations (idempotent) ---"
cd "$DL"
php scripts/apply-dreamland-creator-approval-migration.php 2>&1 | tail -3 || true

echo ""
echo "--- Process stuck safety queue ---"
php scripts/process-safety-queue.php 50 2>&1 || true

echo ""
echo "--- Clear Yii schema cache ---"
rm -rf "$DL/backend/runtime/cache/"* 2>/dev/null || true
rm -rf "$DL/api/runtime/cache/"* 2>/dev/null || true
rm -rf "$DL/common/runtime/cache/"* 2>/dev/null || true
echo "Schema cache cleared."

echo ""
echo "--- Creator status check (user 7 = Afiagold if registered) ---"
php scripts/check-creator.php 7 2>&1 || true

echo ""
echo "Done."
echo "Admin creators: https://dreamlandgh.app/admin/index.php?r=content-creator/index"
echo "Safety queue:   https://dreamlandgh.app/admin/index.php?r=dreamland-safety/index"
echo "To approve Afiagold if still pending: php scripts/check-creator.php 7 approve"
echo "Cron (optional): */5 * * * * cd ~/dreamland && php scripts/process-safety-queue.php 25"
