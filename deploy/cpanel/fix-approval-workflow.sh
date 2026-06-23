#!/bin/bash
# Fix end-to-end reel approval: safety → AI moderation → appraisal → PWA feed.
# Run: curl -fsSL https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-approval-workflow.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
TMP="/tmp/dreamland-approval-$$"
BASE="$GITHUB/backend/sayhi_v1.6_code"

mkdir -p "$TMP"
trap 'rm -rf "$TMP"' EXIT

echo "=== Dreamland approval workflow fix ==="

fetch() { curl -fsSL -o "$1" "$2"; }

fetch "$TMP/DreamlandSafetyPipeline.php" "$BASE/common/components/DreamlandSafetyPipeline.php"
fetch "$TMP/DreamlandAppraisalService.php" "$BASE/common/components/DreamlandAppraisalService.php"
fetch "$TMP/DreamlandAppraisalController.php" "$BASE/backend/controllers/DreamlandAppraisalController.php"
fetch "$TMP/DreamlandModerationController.php" "$BASE/backend/controllers/DreamlandModerationController.php"
fetch "$TMP/AdminAppraisalController.php" "$BASE/api/modules/v1/controllers/AdminAppraisalController.php"
fetch "$TMP/CreatorController.php" "$BASE/api/modules/v1/controllers/CreatorController.php"
fetch "$TMP/Post.php" "$BASE/common/models/Post.php"
fetch "$TMP/appraisal-index.php" "$BASE/backend/views/dreamland-appraisal/index.php"
fetch "$TMP/appraisal-preview.php" "$BASE/backend/views/dreamland-appraisal/preview.php"
fetch "$TMP/moderation-index.php" "$BASE/backend/views/dreamland-moderation/index.php"
fetch "$TMP/reel-view.php" "$BASE/backend/views/audio/reel-view.php"
fetch "$TMP/process-safety-queue.php" "$BASE/scripts/process-safety-queue.php"
fetch "$TMP/repair-appraisal-post.php" "$BASE/scripts/repair-appraisal-post.php"

install() {
  cp -f "$1" "$2"
  chmod u+rw "$2" 2>/dev/null || true
  echo "OK: $2"
}

install "$TMP/DreamlandSafetyPipeline.php" "$DL/common/components/DreamlandSafetyPipeline.php"
install "$TMP/DreamlandAppraisalService.php" "$DL/common/components/DreamlandAppraisalService.php"
install "$TMP/DreamlandAppraisalController.php" "$DL/backend/controllers/DreamlandAppraisalController.php"
install "$TMP/DreamlandModerationController.php" "$DL/backend/controllers/DreamlandModerationController.php"
install "$TMP/AdminAppraisalController.php" "$DL/api/modules/v1/controllers/AdminAppraisalController.php"
install "$TMP/CreatorController.php" "$DL/api/modules/v1/controllers/CreatorController.php"
install "$TMP/Post.php" "$DL/common/models/Post.php"
install "$TMP/appraisal-index.php" "$DL/backend/views/dreamland-appraisal/index.php"
install "$TMP/appraisal-preview.php" "$DL/backend/views/dreamland-appraisal/preview.php"
install "$TMP/moderation-index.php" "$DL/backend/views/dreamland-moderation/index.php"
install "$TMP/reel-view.php" "$DL/backend/views/audio/reel-view.php"
install "$TMP/process-safety-queue.php" "$DL/scripts/process-safety-queue.php"
install "$TMP/repair-appraisal-post.php" "$DL/scripts/repair-appraisal-post.php"

rm -rf "$DL/api/runtime/cache/"* "$DL/backend/runtime/cache/"* 2>/dev/null || true

echo ""
echo "--- Process pending safety queue ---"
cd "$DL"
php scripts/process-safety-queue.php 50 2>&1 || true

echo ""
echo "Done. Flow: Upload → Safety queue → AI moderation → Appraisal → PWA feed."
echo "Appraisal: https://dreamlandgh.app/admin/dreamland-appraisal"
