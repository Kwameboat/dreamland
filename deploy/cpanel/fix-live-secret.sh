#!/bin/bash
# Sync DREAMLAND_LIVE_SECRET on cPanel so PHP can register rooms on Fly.io.
# MUST match: fly secrets list -a dreamland-live  →  DREAMLAND_LIVE_SECRET
#
# Run on cPanel:
#   LIVE_SECRET=your-fly-secret bash <(curl -fsSL -A "DreamlandDeploy/1.0" https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-live-secret.sh)
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
ENV="$HOME_DIR/dreamland/.env"
FLY_URL="${DREAMLAND_LIVE_FLY_URL:-https://dreamland-live.fly.dev}"

if [ -z "${LIVE_SECRET:-}" ]; then
  echo "ERROR: LIVE_SECRET is required."
  echo "  Get value from Fly: fly secrets list -a dreamland-live"
  echo "  Then run: LIVE_SECRET=... curl -fsSL .../fix-live-secret.sh | bash"
  exit 1
fi

upsert() {
  local key="$1" val="$2"
  touch "$ENV"
  if grep -q "^${key}=" "$ENV" 2>/dev/null; then
    sed -i "s|^${key}=.*|${key}=${val}|" "$ENV"
  else
    echo "${key}=${val}" >> "$ENV"
  fi
}

echo "=== Dreamland live secret sync ==="
upsert DREAMLAND_LIVE_SERVER_URL "$FLY_URL"
upsert DREAMLAND_LIVE_SIGNALING_URL "$FLY_URL"
upsert DREAMLAND_LIVE_SECRET "$LIVE_SECRET"

rm -rf "$HOME_DIR/dreamland/api/runtime/cache/"* 2>/dev/null || true

echo "Updated $ENV (SECRET hidden):"
grep -E '^DREAMLAND_LIVE_' "$ENV" | sed 's/SECRET=.*/SECRET=***/' || true

echo ""
echo "Checking live_register_ok..."
HEALTH="$(curl -fsSL -A "DreamlandDeploy/1.0" "https://dreamlandgh.app/api/v1/health" 2>/dev/null || echo '{}')"
echo "$HEALTH" | grep -o '"live_register_ok":[^,}]*' || echo "live_register_ok not in response (deploy HealthController first)"

if echo "$HEALTH" | grep -q '"live_register_ok":true'; then
  echo "OK: PHP can register live rooms on Fly.io"
  exit 0
fi

echo ""
echo "FAIL: live_register_ok is still false."
echo "  1. On your PC run: fly secrets set DREAMLAND_LIVE_SECRET=\"\$LIVE_SECRET\" -a dreamland-live"
echo "  2. Wait 30s for Fly machine restart"
echo "  3. Re-run this script with the SAME LIVE_SECRET value"
exit 1
