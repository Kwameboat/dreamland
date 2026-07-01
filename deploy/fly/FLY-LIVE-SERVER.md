# Deploy Dreamland Live on Fly.io (recommended)

Render only exposes HTTP — **WebRTC video/audio needs UDP**, which Render does not provide. That is why viewers get stuck on "Loading live video" even after signaling connects.

Fly.io exposes UDP ports for mediasoup. Use this for real live broadcasts.

## Prerequisites

- [Fly.io account](https://fly.io) and `flyctl` installed
- GitHub repo **Kwameboat/dreamland** cloned locally

## Step 1 — Create app

```bash
cd live-server
fly launch --no-deploy --name dreamland-live --region iad
```

Accept the generated `fly.toml` (repo already includes one) or copy from the repo.

## Step 2 — Secrets

Generate a secret (same value will go on cPanel):

```bash
fly secrets set DREAMLAND_LIVE_SECRET="YOUR_LONG_RANDOM_SECRET"
```

## Step 3 — Deploy

```bash
fly deploy
```

Note your URL, e.g. `https://dreamland-live.fly.dev`

Verify:

```bash
curl -sS https://dreamland-live.fly.dev/health
```

## Step 4 — Wire cPanel API

In **cPanel Terminal**:

```bash
LIVE_URL="https://dreamland-live.fly.dev" \
LIVE_SECRET="YOUR_LONG_RANDOM_SECRET" \
bash <<'EOF'
curl -fsSL -A "DreamlandDeploy/1.0" \
  https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/wire-live-render.sh | bash
EOF
```

(`wire-live-render.sh` works for any live-server URL, not only Render.)

## Step 5 — Test

1. Hard-refresh PWA: **Ctrl+Shift+R** on https://dreamlandgh.app
2. Creator → **Go live** — if camera can't reach server, you'll see an error (not a fake "You are live")
3. Viewer → watch live — should pass "Loading live video" and play

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| Host: "Camera could not reach the live server" | Fly app not running, wrong secret, or firewall — check `fly logs` |
| Viewer: "Loading live video" forever | Still pointing at Render — re-run wire script with Fly URL |
| `live_server: false` on API health | `.env` on cPanel wrong — check `DREAMLAND_LIVE_SERVER_URL` |

## Cost

Fly.io shared-cpu-1x is typically ~$5–7/mo with `min_machines_running = 1`. Cheaper than unreliable live video on Render.
