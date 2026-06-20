#!/bin/bash
set -e

PORT="${PORT:-80}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \\*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

UPLOAD_ROOT="${DREAMLAND_UPLOAD_DIR:-/app/api/runtime/uploads}"
mkdir -p \
  "$UPLOAD_ROOT/user" \
  "$UPLOAD_ROOT/image" \
  "$UPLOAD_ROOT/video" \
  "$UPLOAD_ROOT/story" \
  "$UPLOAD_ROOT/chat"
chown -R www-data:www-data /app/api/runtime "$UPLOAD_ROOT" 2>/dev/null || true
chmod -R 775 "$UPLOAD_ROOT" 2>/dev/null || true

exec apache2-foreground
