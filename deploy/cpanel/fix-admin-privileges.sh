#!/bin/bash
# Register Dreamland admin modules + enforce module privileges on cPanel.
# Run: curl -fsSL https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-admin-privileges.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
BASE="$GITHUB/backend/sayhi_v1.6_code"
TMP="/tmp/dreamland-privileges-$$"

mkdir -p "$TMP"
trap 'rm -rf "$TMP"' EXIT

echo "=== Dreamland admin module privileges ==="

fetch() { curl -fsSL -o "$1" "$2"; }

fetch "$TMP/AuthPermission.php" "$BASE/backend/components/AuthPermission.php"
fetch "$TMP/AdministratorController.php" "$BASE/backend/controllers/AdministratorController.php"
fetch "$TMP/auth-permission.php" "$BASE/backend/views/administrator/auth-permission.php"
fetch "$TMP/index.php" "$BASE/backend/views/administrator/index.php"
fetch "$TMP/left.php" "$BASE/backend/views/layouts/left.php"
fetch "$TMP/DreamlandAppraisalController.php" "$BASE/backend/controllers/DreamlandAppraisalController.php"
fetch "$TMP/DreamlandModerationController.php" "$BASE/backend/controllers/DreamlandModerationController.php"
fetch "$TMP/DreamlandSafetyController.php" "$BASE/backend/controllers/DreamlandSafetyController.php"
fetch "$TMP/DreamlandSettingsController.php" "$BASE/backend/controllers/DreamlandSettingsController.php"
fetch "$TMP/CreditPackageController.php" "$BASE/backend/controllers/CreditPackageController.php"
fetch "$TMP/WithdrawalPaymentController.php" "$BASE/backend/controllers/WithdrawalPaymentController.php"
fetch "$TMP/apply-dreamland-module-privileges.php" "$BASE/scripts/apply-dreamland-module-privileges.php"
fetch "$TMP/dreamland-admin.css" "$BASE/backend/web/css/dreamland-admin.css"

install() {
  cp -f "$1" "$2"
  chmod u+rw "$2" 2>/dev/null || true
  echo "OK: $2"
}

install "$TMP/AuthPermission.php" "$DL/backend/components/AuthPermission.php"
install "$TMP/AdministratorController.php" "$DL/backend/controllers/AdministratorController.php"
install "$TMP/auth-permission.php" "$DL/backend/views/administrator/auth-permission.php"
install "$TMP/index.php" "$DL/backend/views/administrator/index.php"
install "$TMP/left.php" "$DL/backend/views/layouts/left.php"
install "$TMP/DreamlandAppraisalController.php" "$DL/backend/controllers/DreamlandAppraisalController.php"
install "$TMP/DreamlandModerationController.php" "$DL/backend/controllers/DreamlandModerationController.php"
install "$TMP/DreamlandSafetyController.php" "$DL/backend/controllers/DreamlandSafetyController.php"
install "$TMP/DreamlandSettingsController.php" "$DL/backend/controllers/DreamlandSettingsController.php"
install "$TMP/CreditPackageController.php" "$DL/backend/controllers/CreditPackageController.php"
install "$TMP/WithdrawalPaymentController.php" "$DL/backend/controllers/WithdrawalPaymentController.php"
install "$TMP/apply-dreamland-module-privileges.php" "$DL/scripts/apply-dreamland-module-privileges.php"
install "$TMP/dreamland-admin.css" "$DL/backend/web/css/dreamland-admin.css"

cd "$DL"
php scripts/apply-dreamland-module-privileges.php

rm -rf "$DL/backend/runtime/cache/"* 2>/dev/null || true

echo ""
echo "Done. Super admins keep full access."
echo "Sub-admins: Administrators → lock icon → tick modules they need."
