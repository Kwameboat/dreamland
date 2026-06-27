#!/bin/bash
# TikTok-speed reels: virtual feed, posters, FFmpeg transcode, CDN URLs.
# Run: curl -fsSL https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-fast-reels.sh | bash
set -euo pipefail

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
WEB="${DREAMLAND_WEB_DIR:-$HOME_DIR/public_html}"
GITHUB="${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}"
BASE="$GITHUB/backend/sayhi_v1.6_code"
WEB_BASE="$GITHUB/web"
UA="DreamlandDeploy/1.0"
TMP="/tmp/dreamland-fast-$$"

mkdir -p "$TMP" "$WEB/js" "$WEB/css"
trap 'rm -rf "$TMP"' EXIT

fetch() { curl -fsSL -A "$UA" -o "$1" "$2"; echo "OK $(basename "$1")"; }

echo "=== Dreamland TikTok-speed reels deploy ==="

# DB migration
fetch "$TMP/apply-dreamland-transcode-migration.php" "$BASE/scripts/apply-dreamland-transcode-migration.php"
cp -f "$TMP/apply-dreamland-transcode-migration.php" "$DL/scripts/apply-dreamland-transcode-migration.php"
cd "$DL" && php scripts/apply-dreamland-transcode-migration.php

# Backend
fetch "$TMP/DreamlandVideoProcessor.php" "$BASE/common/components/DreamlandVideoProcessor.php"
fetch "$TMP/DreamlandMediaUrl.php" "$BASE/common/helpers/DreamlandMediaUrl.php"
fetch "$TMP/CreatorController.php" "$BASE/api/modules/v1/controllers/CreatorController.php"
fetch "$TMP/Post.php" "$BASE/api/modules/v1/models/Post.php"
fetch "$TMP/PostGallary.php" "$BASE/api/modules/v1/models/PostGallary.php"
fetch "$TMP/transcode-existing-reels.php" "$BASE/scripts/transcode-existing-reels.php"

install() { cp -f "$1" "$2"; echo "Installed $2"; }

install "$TMP/DreamlandVideoProcessor.php" "$DL/common/components/DreamlandVideoProcessor.php"
install "$TMP/DreamlandMediaUrl.php" "$DL/common/helpers/DreamlandMediaUrl.php"
install "$TMP/CreatorController.php" "$DL/api/modules/v1/controllers/CreatorController.php"
install "$TMP/Post.php" "$DL/api/modules/v1/models/Post.php"
install "$TMP/PostGallary.php" "$DL/api/modules/v1/models/PostGallary.php"
install "$TMP/transcode-existing-reels.php" "$DL/scripts/transcode-existing-reels.php"

# PWA
fetch "$TMP/app.js" "$WEB_BASE/js/app.js"
fetch "$TMP/dreamland-reels-fast.js" "$WEB_BASE/js/dreamland-reels-fast.js"
fetch "$TMP/index.html" "$WEB_BASE/index.html"
fetch "$TMP/app.css" "$WEB_BASE/css/app.css"
fetch "$TMP/sw.js" "$WEB_BASE/sw.js"
fetch "$TMP/build-version.json" "$WEB_BASE/build-version.json"

install "$TMP/app.js" "$WEB/js/app.js"
install "$TMP/dreamland-reels-fast.js" "$WEB/js/dreamland-reels-fast.js"
install "$TMP/index.html" "$WEB/index.html"
install "$TMP/app.css" "$WEB/css/app.css"
install "$TMP/sw.js" "$WEB/sw.js"
install "$TMP/build-version.json" "$WEB/build-version.json"

rm -rf "$DL/api/runtime/cache/"* 2>/dev/null || true

echo ""
echo "Optional: transcode existing reels (requires ffmpeg on server):"
echo "  cd ~/dreamland && php scripts/transcode-existing-reels.php 20"
echo ""
echo "Done. Hard-refresh PWA: Ctrl+Shift+R"
echo "Build: $(grep -o 'build-[0-9]*' "$WEB/build-version.json" | head -1)"
