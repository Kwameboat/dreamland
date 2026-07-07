#!/bin/bash
# Dreamland live E2E — TikTok-style WebRTC SFU (creator go-live + viewer watch).
# Wires Fly.io edge, deploys PWA client, PHP API, and live-socket proxy.
#
# Run on cPanel:
#   LIVE_SECRET=your-fly-secret bash <(curl -fsSL -A "DreamlandDeploy/1.0" https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-live-e2e.sh)
#
# Or:
#   curl -fsSL -A "DreamlandDeploy/1.0" https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-live-e2e.sh | bash
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

echo "============================================"
echo " Dreamland Live E2E — Fly.io WebRTC SFU"
echo "============================================"

upsert DREAMLAND_LIVE_SERVER_URL "$FLY_URL"
upsert DREAMLAND_LIVE_SIGNALING_URL "$FLY_URL"

if [ -n "${LIVE_SECRET:-}" ]; then
  upsert DREAMLAND_LIVE_SECRET "$LIVE_SECRET"
elif ! grep -q '^DREAMLAND_LIVE_SECRET=' "$ENV" 2>/dev/null; then
  echo "WARN: Set LIVE_SECRET=... (must match Fly secret)"
fi

echo ""
echo "Wired .env:"
grep -E '^DREAMLAND_LIVE_' "$ENV" | sed 's/SECRET=.*/SECRET=***/' || true

rm -rf "$HOME_DIR/dreamland/api/runtime/cache/"* 2>/dev/null || true

echo ""
echo "Deploying PWA + API + live-socket proxy..."
curl -fsSL -A "DreamlandDeploy/1.0" "$GITHUB/deploy/cpanel/fix-live-signal.sh" | bash

echo ""
echo "Verifying Fly edge..."
FLY_HEALTH="$(curl -fsSL -A "DreamlandDeploy/1.0" "$FLY_URL/health" 2>/dev/null || echo '{}')"
echo "$FLY_HEALTH" | head -c 220
echo ""

echo ""
echo "Verifying same-origin proxy..."
PROXY="$(curl -fsSL -A "DreamlandDeploy/1.0" "https://dreamlandgh.app/live-socket/health" 2>/dev/null || echo '{}')"
echo "$PROXY" | head -c 220
echo ""

if echo "$FLY_HEALTH" | grep -q '"deploy":"fly"'; then
  echo "OK: Fly.io edge healthy"
else
  echo "WARN: Fly health check failed — run: fly deploy (from live-server/)"
fi

if echo "$PROXY" | grep -q '"deploy":"fly"'; then
  echo "OK: Proxy points to Fly.io"
else
  echo "WARN: Proxy may still hit Render — check $ENV"
fi

BUILD="$(curl -fsSL -A "DreamlandDeploy/1.0" "$GITHUB/web/build-version.json" 2>/dev/null | grep -o 'build-[0-9]*' | head -1 || echo unknown)"
echo ""
echo "============================================"
echo " Done ($BUILD)"
echo " 1. Hard-refresh PWA (Ctrl+Shift+R)"
echo " 2. Creator: end old broadcast, Go live fresh"
echo " 3. Viewer: tap Watch on live card"
echo "============================================"
