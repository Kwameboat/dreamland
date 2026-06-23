#!/bin/bash
# Fix Dreamland Settings admin page (Unknown Property / 500).
# Run: curl -fsSL https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-dreamland-settings.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
TMP="/tmp/dreamland-settings-fix-$$"
BASE="$GITHUB/backend/sayhi_v1.6_code"

mkdir -p "$TMP"
trap 'rm -rf "$TMP"' EXIT

echo "=== Fix Dreamland Settings admin page ==="

fetch() { curl -fsSL -o "$1" "$2"; }

fetch "$TMP/DreamlandSetting.php" "$BASE/common/models/DreamlandSetting.php"
fetch "$TMP/DreamlandSettingsController.php" "$BASE/backend/controllers/DreamlandSettingsController.php"
fetch "$TMP/index.php" "$BASE/backend/views/dreamland-settings/index.php"
fetch "$TMP/apply-dreamland-settings-migration.php" "$BASE/scripts/apply-dreamland-settings-migration.php"

cp -f "$TMP/DreamlandSetting.php" "$DL/common/models/DreamlandSetting.php"
cp -f "$TMP/DreamlandSettingsController.php" "$DL/backend/controllers/DreamlandSettingsController.php"
cp -f "$TMP/index.php" "$DL/backend/views/dreamland-settings/index.php"
cp -f "$TMP/apply-dreamland-settings-migration.php" "$DL/scripts/apply-dreamland-settings-migration.php"
echo "OK: PHP files installed"

cd "$DL"
php scripts/apply-dreamland-settings-migration.php 2>&1 || true
rm -rf backend/runtime/cache/* common/runtime/cache/* 2>/dev/null || true

echo ""
echo "Done. Open: https://dreamlandgh.app/admin/index.php?r=dreamland-settings"
