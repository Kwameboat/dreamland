#!/bin/bash
# Run on cPanel Terminal after uploading dreamland-cpanel.zip to ~/home
# Usage: bash remote-install.sh

set -e
HOME_DIR="${HOME:-/home/$(whoami)}"
cd "$HOME_DIR"

if [ ! -f dreamland-cpanel.zip ]; then
  echo "Upload dreamland-cpanel.zip to $HOME_DIR first (File Manager or SCP)."
  exit 1
fi

echo "=== Extract ==="
rm -rf dreamland-cpanel-tmp
mkdir -p dreamland-cpanel-tmp
unzip -o -q dreamland-cpanel.zip -d dreamland-cpanel-tmp

echo "=== Install dreamland/ ==="
rm -rf dreamland.bak
[ -d dreamland ] && mv dreamland dreamland.bak
mv dreamland-cpanel-tmp/dreamland dreamland

echo "=== Merge public_html ==="
shopt -s dotglob
for item in dreamland-cpanel-tmp/public_html/*; do
  name=$(basename "$item")
  if [ "$name" = "admin" ] || [ "$name" = "api" ]; then
    rm -rf "public_html/$name"
    cp -a "$item" "public_html/$name"
  else
    cp -a "$item" public_html/
  fi
done
rm -rf dreamland-cpanel-tmp

echo "=== Composer ==="
cd "$HOME_DIR/dreamland"
if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --optimize-autoloader --no-interaction
elif [ -f /opt/cpanel/composer/bin/composer ]; then
  /opt/cpanel/composer/bin/composer install --no-dev --optimize-autoloader --no-interaction
else
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  php composer-setup.php --quiet
  php composer.phar install --no-dev --optimize-autoloader --no-interaction
fi

chmod -R 775 api/runtime backend/runtime common/runtime backend/web/assets 2>/dev/null || true

echo ""
echo "=== Done ==="
echo "PWA:   https://dreamlandgh.app"
echo "Admin: https://dreamlandgh.app/admin/site/login"
echo "API:   https://dreamlandgh.app/api/v1/health"
echo ""
echo "Next: edit ~/dreamland/.env — add Wasabi keys + COOKIE_VALIDATION_KEY + DB password"
