#!/bin/bash
# Link admin static files so /admin/css, /admin/img, /admin/assets are served by LiteSpeed.
set -e
HOME_DIR="${HOME:-/home/$(whoami)}"
ADMIN="$HOME_DIR/public_html/admin"
WEB="$HOME_DIR/dreamland/backend/web"

chmod u+x "$WEB" 2>/dev/null || true
mkdir -p "$WEB/assets"
chmod -R u+rwX "$WEB/assets"

for name in css img assets; do
  if [ -d "$WEB/$name" ]; then
    rm -rf "$ADMIN/$name"
    ln -sfn "$WEB/$name" "$ADMIN/$name"
    echo "OK: $ADMIN/$name -> $WEB/$name"
  fi
done

echo "Done. Reload https://dreamlandgh.app/admin/site/login"
