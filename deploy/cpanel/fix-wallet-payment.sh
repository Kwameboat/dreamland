#!/bin/bash
# Fix wallet top-up: verify Paystack return + credit grant on cPanel.
# Run: curl -fsSL https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-wallet-payment.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
BASE="$GITHUB/backend/sayhi_v1.6_code"
TMP="/tmp/dreamland-wallet-$$"

mkdir -p "$TMP"
trap 'rm -rf "$TMP"' EXIT

echo "=== Dreamland wallet payment fix ==="

fetch() { curl -fsSL -o "$1" "$2"; }

fetch "$TMP/DreamlandWalletService.php" "$BASE/common/components/DreamlandWalletService.php"
fetch "$TMP/WalletController.php" "$BASE/api/modules/v1/controllers/WalletController.php"
fetch "$TMP/apply-dreamland-paystack-migration.php" "$BASE/scripts/apply-dreamland-paystack-migration.php"
fetch "$TMP/app.js" "$GITHUB/web/js/app.js"
fetch "$TMP/params.php" "$BASE/common/config/params.php"

install() {
  cp -f "$1" "$2"
  chmod u+rw "$2" 2>/dev/null || true
  echo "OK: $2"
}

install "$TMP/DreamlandWalletService.php" "$DL/common/components/DreamlandWalletService.php"
install "$TMP/WalletController.php" "$DL/api/modules/v1/controllers/WalletController.php"
install "$TMP/apply-dreamland-paystack-migration.php" "$DL/scripts/apply-dreamland-paystack-migration.php"
install "$TMP/params.php" "$DL/common/config/params.php"

WEB="$HOME_DIR/public_html"
if [ -d "$WEB" ]; then
  mkdir -p "$WEB/js"
  install "$TMP/app.js" "$WEB/js/app.js"
fi

cd "$DL"
php scripts/apply-dreamland-paystack-migration.php

rm -rf "$DL/api/runtime/cache/"* 2>/dev/null || true

echo ""
echo "Done. After Paystack payment, users return to /wallet/callback and credits are verified."
echo "Set DREAMLAND_PWA_URL=https://dreamlandgh.app in .env if callback URL must match your domain."
echo "Configure Paystack webhook: POST https://api.dreamlandgh.app/v1/wallet/paystack-webhook"
