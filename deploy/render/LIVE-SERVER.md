# Deploy Dreamland Live on Render

Dreamland Live is the **Node.js WebRTC server** (`live-server/`) that powers creator go-live and viewer watch.

## Important: Render limits

| Works on Render | May not work on Render |
|-----------------|------------------------|
| `/health` (API checks) | UDP media ports (mediasoup default) |
| Socket.IO signaling (`wss://`) | Reliable video/audio in all networks |
| Room registration from cPanel API | |

Render **web services do not expose UDP** for mediasoup RTP. Signaling and health will work; **video may stay black** on some devices. For production-grade live video, use **Fly.io**, **Railway** (with UDP), or a **VPS** instead.

This guide still deploys on Render so you can unblock API health checks and test signaling.

---

## Step 1 — Create Render Web Service

1. Go to [render.com](https://render.com) → **New** → **Web Service**
2. Connect GitHub repo: **Kwameboat/dreamland**
3. Settings:

| Field | Value |
|-------|--------|
| **Name** | `dreamland-live` |
| **Root Directory** | `live-server` |
| **Runtime** | Node |
| **Build Command** | `npm install` |
| **Start Command** | `npm start` |
| **Health Check Path** | `/health` |

4. **Instance type**: Starter or higher (free tier sleeps after 15 min — first go-live may be slow)

---

## Step 2 — Environment variables (Render)

In Render → your service → **Environment**:

| Key | Value |
|-----|--------|
| `NODE_ENV` | `production` |
| `DREAMLAND_LIVE_DEPLOY` | `render` |
| `DREAMLAND_LIVE_CORS` | `https://dreamlandgh.app` |
| `DREAMLAND_LIVE_SECRET` | Generate a long random string (save it!) |

Render sets `PORT` automatically — do not set `DREAMLAND_LIVE_PORT`.

**Copy your live URL**, e.g. `https://dreamland-live-xxxx.onrender.com`

---

## Step 3 — Verify Render service

```bash
curl -sS https://YOUR-LIVE-SERVICE.onrender.com/health
```

Expected:

```json
{"ok":true,"service":"dreamland-live","rooms":0,"uptime":...}
```

---

## Step 4 — Wire cPanel API (dreamlandgh.app)

SSH or **cPanel Terminal**:

```bash
bash <<'EOF'
LIVE_URL="https://YOUR-LIVE-SERVICE.onrender.com"
LIVE_SECRET="PASTE_SAME_SECRET_AS_RENDER"
ENV=~/dreamland/.env

touch "$ENV"
grep -q '^DREAMLAND_LIVE_SERVER_URL=' "$ENV" \
  && sed -i "s|^DREAMLAND_LIVE_SERVER_URL=.*|DREAMLAND_LIVE_SERVER_URL=$LIVE_URL|" "$ENV" \
  || echo "DREAMLAND_LIVE_SERVER_URL=$LIVE_URL" >> "$ENV"

grep -q '^DREAMLAND_LIVE_SIGNALING_URL=' "$ENV" \
  && sed -i "s|^DREAMLAND_LIVE_SIGNALING_URL=.*|DREAMLAND_LIVE_SIGNALING_URL=$LIVE_URL|" "$ENV" \
  || echo "DREAMLAND_LIVE_SIGNALING_URL=$LIVE_URL" >> "$ENV"

grep -q '^DREAMLAND_LIVE_SECRET=' "$ENV" \
  && sed -i "s|^DREAMLAND_LIVE_SECRET=.*|DREAMLAND_LIVE_SECRET=$LIVE_SECRET|" "$ENV" \
  || echo "DREAMLAND_LIVE_SECRET=$LIVE_SECRET" >> "$ENV"

rm -rf ~/dreamland/api/runtime/cache/*
echo "Done. Testing API health..."
curl -sS "https://dreamlandgh.app/api/v1/health" | grep -E 'live_server|live_signaling'
EOF
```

Replace `YOUR-LIVE-SERVICE` and `PASTE_SAME_SECRET_AS_RENDER`.

You want `"live_server":true` in the health response.

---

## Step 5 — Update PWA + test

1. Hard-refresh PWA: **Ctrl+Shift+R** on https://dreamlandgh.app
2. Creator → **Go live** → allow camera
3. If you see *"You are live"* but video is black for viewers → Render UDP limit; plan Fly.io/VPS migration

---

## Blueprint deploy (optional)

Repo includes `live-server/render.yaml`. In Render:

**New → Blueprint** → select repo → apply.

Then complete Step 4 with the generated URL and `DREAMLAND_LIVE_SECRET`.

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| `live_server: false` | Wrong URL in `.env`, secret mismatch, or Render service sleeping |
| Stuck on STARTING… | Run `fix-live-broadcast.sh` on cPanel; check API health |
| Signaling timeout | Render cold start — wait 30–60s and retry |
| Black video / no audio | Render UDP limitation — deploy live-server on Fly.io or VPS |
| 403 on `/internal/rooms` | `DREAMLAND_LIVE_SECRET` must match on Render and cPanel |

---

## Upgrade path (recommended for real broadcasts)

When ready for reliable live video:

1. Deploy `live-server/` on **Fly.io** (UDP supported) or a **VPS**
2. Point the same three `.env` keys on cPanel to the new URL
3. Set `DREAMLAND_LIVE_DEPLOY=fly` (or remove it for full UDP)
