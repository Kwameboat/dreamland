<?php
/**
 * Remove broken empty schemaMap from main-local.php (MySQL on cPanel).
 * Usage: php deploy/cpanel/scripts/fix-mysql-schemamap.php
 */
declare(strict_types=1);

$root = dirname(__DIR__, 3);
$file = $root . '/common/config/main-local.php';

if (!is_file($file)) {
    fwrite(STDERR, "Missing {$file}\n");
    exit(1);
}

$contents = file_get_contents($file);
$original = $contents;

// Legacy template set schemaMap to [] for mysql — that disables Yii's mysql schema driver.
$patterns = [
    "/\s*['\"]schemaMap['\"]\s*=>\s*\\\$driver\s*===\s*['\"]pgsql['\"]\s*\?\s*\[[\s\S]*?\]\s*:\s*\[\s*\]\s*,?\s*/",
    "/\s*['\"]schemaMap['\"]\s*=>\s*\[\s*\]\s*,?\s*\n?/",
];

foreach ($patterns as $pattern) {
    $contents = preg_replace($pattern, '', $contents) ?? $contents;
}

if ($contents === $original) {
    echo "No empty schemaMap found (already OK).\n";
    exit(0);
}

file_put_contents($file, $contents);
echo "Fixed main-local.php: removed empty schemaMap for MySQL.\n";
