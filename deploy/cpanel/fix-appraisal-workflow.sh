#!/bin/bash
# Fix appraisal approve integrity errors (missing gamification tables / duplicate pot rows).
# Run: curl -fsSL https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-appraisal-workflow.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
TMP="/tmp/dreamland-appraisal-$$"
BASE="$GITHUB/backend/sayhi_v1.6_code"

mkdir -p "$TMP"
trap 'rm -rf "$TMP"' EXIT

echo "=== Dreamland appraisal workflow fix ==="

fetch() { curl -fsSL -o "$1" "$2"; }

fetch "$TMP/DreamlandAppraisalService.php" "$BASE/common/components/DreamlandAppraisalService.php"
fetch "$TMP/DreamlandAppraisalController.php" "$BASE/backend/controllers/DreamlandAppraisalController.php"
fetch "$TMP/AdminAppraisalController.php" "$BASE/api/modules/v1/controllers/AdminAppraisalController.php"
fetch "$TMP/GroupWatchPot.php" "$BASE/common/models/GroupWatchPot.php"
fetch "$TMP/VideoPrediction.php" "$BASE/common/models/VideoPrediction.php"
fetch "$TMP/index.php" "$BASE/backend/views/dreamland-appraisal/index.php"
fetch "$TMP/dreamland_gamification_mysql.sql" "$BASE/doc/db/dreamland_gamification_mysql.sql"
fetch "$TMP/apply-dreamland-gamification-migration.php" "$BASE/scripts/apply-dreamland-gamification-migration.php"

install() {
  cp -f "$1" "$2"
  chmod u+rw "$2" 2>/dev/null || true
  echo "OK: $2"
}

install "$TMP/DreamlandAppraisalService.php" "$DL/common/components/DreamlandAppraisalService.php"
install "$TMP/DreamlandAppraisalController.php" "$DL/backend/controllers/DreamlandAppraisalController.php"
install "$TMP/AdminAppraisalController.php" "$DL/api/modules/v1/controllers/AdminAppraisalController.php"
install "$TMP/GroupWatchPot.php" "$DL/common/models/GroupWatchPot.php"
install "$TMP/VideoPrediction.php" "$DL/common/models/VideoPrediction.php"
install "$TMP/index.php" "$DL/backend/views/dreamland-appraisal/index.php"
mkdir -p "$DL/doc/db"
install "$TMP/dreamland_gamification_mysql.sql" "$DL/doc/db/dreamland_gamification_mysql.sql"
install "$TMP/apply-dreamland-gamification-migration.php" "$DL/scripts/apply-dreamland-gamification-migration.php"

echo ""
echo "--- Gamification tables (group watch pot / predictions) ---"
cd "$DL"
php scripts/apply-dreamland-gamification-migration.php 2>&1 || true

rm -rf "$DL/backend/runtime/cache/"* 2>/dev/null || true
rm -rf "$DL/api/runtime/cache/"* 2>/dev/null || true

echo ""
echo "Done. Retry approving the reel in Appraisal Workspace."
echo "https://dreamlandgh.app/admin/index.php?r=dreamland-appraisal/index"
