#!/bin/bash
# One-shot hotfix on cPanel when API 500 + admin login fails.
# Paste in Terminal:  bash deploy/cpanel/hotfix-schemamap-and-seed.sh
set -e

HOME_DIR="${HOME:-/home/$(whoami)}"
DL="$HOME_DIR/dreamland"
cd "$DL"

echo "=== Dreamland hotfix: schemaMap + admin seed ==="

mkdir -p "$DL/deploy/cpanel/config"

cat > "$DL/deploy/cpanel/config/db-mysql-fix.php" << 'EOF'
<?php
return [
    'components' => [
        'db' => [
            'schemaMap' => [
                'mysql' => 'yii\db\mysql\Schema',
                'mysqli' => 'yii\db\mysql\Schema',
            ],
        ],
    ],
];
EOF
echo "OK: db-mysql-fix.php"

php << PHPFIX
<?php
\$f = '$DL/common/config/main-local.php';
if (!is_file(\$f)) { fwrite(STDERR, "Missing \$f\n"); exit(1); }
\$c = file_get_contents(\$f);
\$orig = \$c;
\$c = preg_replace(
    "/\s*['\"]schemaMap['\"]\s*=>\s*\\\\\$driver\s*===\s*['\"]pgsql['\"]\s*\?\s*\[[\s\S]*?\]\s*:\s*\[\s*\]\s*,?\s*/",
    "\n",
    \$c
) ?? \$c;
\$c = preg_replace("/\s*['\"]schemaMap['\"]\s*=>\s*\[\s*\]\s*,?\s*/", "\n", \$c) ?? \$c;
if (\$c === \$orig && !preg_match("/'mysql'\s*=>\s*'yii\\\\\\\\db\\\\\\\\mysql\\\\\\\\Schema'/", \$c)) {
    \$c = preg_replace(
        "/('class'\s*=>\s*'yii\\\\\\\\db\\\\\\\\Connection',)/",
        "\$1\n            'schemaMap' => ['mysql' => 'yii\\\\db\\\\mysql\\\\Schema', 'mysqli' => 'yii\\\\db\\\\mysql\\\\Schema'],",
        \$c,
        1
    ) ?? \$c;
}
file_put_contents(\$f, \$c);
echo "OK: main-local.php schemaMap fixed\n";
PHPFIX

merge_db_fix() {
  local file="$1"
  [ -f "$file" ] || return 0
  if grep -q 'db-mysql-fix.php' "$file"; then
    echo "Already patched: $file"
    return 0
  fi
  sed -i "s|require \$yiiRoot . '/deploy/cpanel/config/backend-subdir.php'|require \$yiiRoot . '/deploy/cpanel/config/backend-subdir.php',\n    require \$yiiRoot . '/deploy/cpanel/config/db-mysql-fix.php'|" "$file" 2>/dev/null || true
  sed -i "s|require \$yiiRoot . '/deploy/cpanel/config/api-subdir.php'|require \$yiiRoot . '/deploy/cpanel/config/api-subdir.php',\n    require \$yiiRoot . '/deploy/cpanel/config/db-mysql-fix.php'|" "$file" 2>/dev/null || true
  echo "Patched entrypoint: $file"
}

merge_db_fix "$HOME_DIR/public_html/admin/index.php"
merge_db_fix "$HOME_DIR/public_html/api/index.php"
merge_db_fix "$HOME_DIR/public_html/api/boot-test.php"

echo ""
echo "--- Seed admin user (admin / demo123) ---"
php "$DL/scripts/seed-demo-data.php" 2>&1 | tail -15

echo ""
echo "--- Verify ---"
curl -sS "https://dreamlandgh.app/api/v1/health" | head -c 400
echo ""
curl -sS -o /dev/null -w "api health: HTTP %{http_code}\n" "https://dreamlandgh.app/api/v1/health"
echo "Done. Login: https://dreamlandgh.app/admin/site/login  (admin / demo123)"
