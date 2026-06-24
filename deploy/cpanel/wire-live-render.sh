#!/bin/bash
# Wire cPanel API to a Render (or any) live-server URL.
# Usage:
#   LIVE_URL=https://dreamland-live-xxxx.onrender.com \
#   LIVE_SECRET=your-secret \
#   bash deploy/cpanel/wire-live-render.sh
#
# Or from cPanel after downloading:
#   curl -fsSL .../wire-live-render.sh -o /tmp/wire.sh && \
#   LIVE_URL=... LIVE_SECRET=... bash /tmp/wire.sh
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
ENV="$HOME_DIR/dreamland/.env"

if [ -z "${LIVE_URL:-}" ] || [ -z "${LIVE_SECRET:-}" ]; then
  echo "Set LIVE_URL and LIVE_SECRET environment variables."
  echo "Example:"
  echo "  LIVE_URL=https://dreamland-live-xxxx.onrender.com LIVE_SECRET=abc123 bash $0"
  exit 1
fi

LIVE_URL="${LIVE_URL%/}"

touch "$ENV"
upsert() {
  local key="$1" val="$2"
  if grep -q "^${key}=" "$ENV" 2>/dev/null; then
    sed -i "s|^${key}=.*|${key}=${val}|" "$ENV"
  else
    echo "${key}=${val}" >> "$ENV"
  fi
}

upsert DREAMLAND_LIVE_SERVER_URL "$LIVE_URL"
upsert DREAMLAND_LIVE_SIGNALING_URL "$LIVE_URL"
upsert DREAMLAND_LIVE_SECRET "$LIVE_SECRET"

rm -rf "$HOME_DIR/dreamland/api/runtime/cache/"* 2>/dev/null || true

echo "Wired live server:"
echo "  DREAMLAND_LIVE_SERVER_URL=$LIVE_URL"
echo "  DREAMLAND_LIVE_SIGNALING_URL=$LIVE_URL"
echo ""
curl -sS "$LIVE_URL/health" || echo "WARN: live /health unreachable (Render may be waking up)"
echo ""
curl -sS "https://dreamlandgh.app/api/v1/health" | grep -E 'live_server|live_signaling' || true
echo ""
echo "Hard-refresh PWA: Ctrl+Shift+R"
