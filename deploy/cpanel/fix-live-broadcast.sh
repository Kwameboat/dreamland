#!/bin/bash
# Fix creator go-live: API checks + PWA timeouts + live-server env hints.
# Run: curl -fsSL https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-live-broadcast.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
WEB="$HOME_DIR/public_html"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
BASE="$GITHUB/backend/sayhi_v1.6_code"
TMP="/tmp/dreamland-live-fix-$$"
STAMP="$(date +%Y%m%d-%H%M%S)"

echo "=== Dreamland live broadcast fix ==="

mkdir -p "$TMP" "$WEB/js"
trap 'rm -rf "$TMP"' EXIT

fetch() { curl -fsSL -o "$1" "$2"; }

cp -f "$WEB/js/app.js" "$WEB/js/app.js.bak-$STAMP" 2>/dev/null || true

fetch "$TMP/CreatorController.php" "$BASE/api/modules/v1/controllers/CreatorController.php"
fetch "$TMP/DreamlandLiveRtcService.php" "$BASE/common/components/DreamlandLiveRtcService.php"
fetch "$TMP/app.js" "$GITHUB/web/js/app.js"
fetch "$TMP/dreamland-live.js" "$GITHUB/web/js/dreamland-live.js"

cp -f "$TMP/CreatorController.php" "$DL/api/modules/v1/controllers/CreatorController.php"
cp -f "$TMP/DreamlandLiveRtcService.php" "$DL/common/components/DreamlandLiveRtcService.php"
cp -f "$TMP/app.js" "$WEB/js/app.js"
cp -f "$TMP/dreamland-live.js" "$WEB/js/dreamland-live.js"
chmod u+rw "$DL/api/modules/v1/controllers/CreatorController.php" "$WEB/js/app.js" "$WEB/js/dreamland-live.js" 2>/dev/null || true

rm -rf "$DL/api/runtime/cache/"* 2>/dev/null || true

echo ""
echo "--- Live server health ---"
HEALTH="$(curl -sS "https://dreamlandgh.app/api/v1/health" || true)"
echo "$HEALTH" | head -c 500
echo ""

if echo "$HEALTH" | grep -q '"live_server":false'; then
  echo ""
  echo "LIVE SERVER IS OFFLINE — go-live cannot work until you:"
  echo "  1) Deploy live-server/ on Railway/Render (Node.js, port 4443, UDP 40000-49999)"
  echo "  2) Add to ~/dreamland/.env:"
  echo "       DREAMLAND_LIVE_SERVER_URL=https://YOUR-LIVE-SERVER.up.railway.app"
  echo "       DREAMLAND_LIVE_SIGNALING_URL=https://YOUR-LIVE-SERVER.up.railway.app"
  echo "       DREAMLAND_LIVE_SECRET=your-long-random-secret"
  echo "  3) On the live-server host set the same DREAMLAND_LIVE_SECRET and:"
  echo "       DREAMLAND_LIVE_CORS=https://dreamlandgh.app"
  echo "  4) Re-run this script and hard-refresh the PWA (Ctrl+Shift+R)"
else
  echo ""
  echo "Live server check passed (or not reported). Hard-refresh PWA: Ctrl+Shift+R"
fi

echo ""
echo "Done. Backup: app.js.bak-$STAMP"
