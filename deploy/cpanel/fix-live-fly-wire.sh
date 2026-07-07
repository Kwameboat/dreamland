#!/bin/bash
# Wire cPanel to Fly.io live-server + deploy proxy/client fixes.
# Run on cPanel:
#   LIVE_SECRET=your-fly-secret bash <(curl -fsSL -A "DreamlandDeploy/1.0" https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-live-fly-wire.sh)
#
# Or if secret already in ~/dreamland/.env:
#   curl -fsSL -A "DreamlandDeploy/1.0" https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-live-fly-wire.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
ENV="$HOME_DIR/dreamland/.env"
FLY_URL="${DREAMLAND_LIVE_FLY_URL:-https://dreamland-live.fly.dev}"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"

upsert() {
  local key="$1" val="$2"
  touch "$ENV"
  if grep -q "^${key}=" "$ENV" 2>/dev/null; then
    sed -i "s|^${key}=.*|${key}=${val}|" "$ENV"
  else
    echo "${key}=${val}" >> "$ENV"
  fi
}

echo "=== Dreamland live → Fly.io wire + deploy ==="

upsert DREAMLAND_LIVE_SERVER_URL "$FLY_URL"
upsert DREAMLAND_LIVE_SIGNALING_URL "$FLY_URL"

if [ -n "${LIVE_SECRET:-}" ]; then
  upsert DREAMLAND_LIVE_SECRET "$LIVE_SECRET"
elif ! grep -q '^DREAMLAND_LIVE_SECRET=' "$ENV" 2>/dev/null; then
  echo "WARN: Set LIVE_SECRET=... (must match Fly: fly secrets list -a dreamland-live)"
fi

echo "Wired .env:"
grep -E '^DREAMLAND_LIVE_' "$ENV" | sed 's/SECRET=.*/SECRET=***/' || true

rm -rf "$HOME_DIR/dreamland/api/runtime/cache/"* 2>/dev/null || true

echo ""
echo "Deploying PWA + live-socket proxy..."
curl -fsSL -A "DreamlandDeploy/1.0" "$GITHUB/deploy/cpanel/fix-live-signal.sh" | bash

echo ""
echo "Verifying Fly direct..."
curl -fsSL -A "DreamlandDeploy/1.0" "$FLY_URL/health" | head -c 200 || echo "WARN: Fly /health failed"
echo ""
echo ""
echo "Verifying same-origin proxy (must show deploy:fly, NOT render)..."
PROXY="$(curl -fsSL -A "DreamlandDeploy/1.0" "https://dreamlandgh.app/live-socket/health" 2>/dev/null || echo '{}')"
echo "$PROXY" | head -c 200
echo ""
if echo "$PROXY" | grep -q '"deploy":"fly"'; then
  echo "OK: Proxy points to Fly.io"
else
  echo "WARN: Proxy may still hit Render — re-run this script or check $ENV"
fi
echo ""
echo "Hard-refresh PWA: Ctrl+Shift+R. End any old broadcast, then Go live again."
