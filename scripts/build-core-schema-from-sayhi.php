<?php
/**
 * Build supabase/migrations/000001_core_schema.sql from bundled sayhi MySQL dump.
 * Does not require Docker or database credentials.
 *
 * Usage:
 *   php scripts/build-core-schema-from-sayhi.php
 */
require __DIR__ . '/lib/mysql-to-pgsql.php';

$sayhiSql = dirname(__DIR__) . '/backend/sayhi_v1.6_code/doc/db/sayhi_v1_6.sql';
if (!is_file($sayhiSql)) {
    fwrite(STDERR, "Missing {$sayhiSql}\n");
    exit(1);
}

$coreTables = [
    'user', 'post', 'post_gallary', 'post_like', 'post_comment', 'post_view', 'post_share',
    'notification', 'follower', 'user_live_history', 'setting', 'category', 'package', 'payment',
];

function extract_mysql_table_ddl(string $content, string $table): ?string
{
    $marker = "-- Table structure for table `{$table}`";
    $pos = strpos($content, $marker);
    if ($pos === false) {
        return null;
    }

    $nextTable = strpos($content, '-- Table structure for table', $pos + strlen($marker));
    $nextInsert = strpos($content, '-- Dumping data for table', $pos);
    $end = min(
        $nextTable !== false ? $nextTable : PHP_INT_MAX,
        $nextInsert !== false ? $nextInsert : PHP_INT_MAX
    );

    $block = substr($content, $pos, $end - $pos);
    if (!preg_match('/(DROP TABLE IF EXISTS `' . preg_quote($table, '/') . '`;.*?^\) ENGINE=[^;]+;)/ms', $block, $m)) {
        return null;
    }

    return $m[1];
}

echo "Reading {$sayhiSql}...\n";
$content = file_get_contents($sayhiSql);
$chunks = [];
$missing = [];

foreach ($coreTables as $table) {
    $ddl = extract_mysql_table_ddl($content, $table);
    if ($ddl === null) {
        $missing[] = $table;
        continue;
    }
    $chunks[] = $ddl;
}

if ($missing) {
    fwrite(STDERR, 'Warning: missing tables in sayhi dump: ' . implode(', ', $missing) . "\n");
}

if (!$chunks) {
    fwrite(STDERR, "No table DDL extracted.\n");
    exit(1);
}

$mysql = implode("\n\n", $chunks);
$pgsql = convert_mysql_schema_to_pgsql($mysql);

if (!preg_match('/CREATE TABLE IF NOT EXISTS "user" \(\s*\n\s+id/i', $pgsql)) {
    fwrite(STDERR, "Converter output looks invalid (user.id missing).\n");
    exit(1);
}

$migrationsDir = dirname(__DIR__) . '/supabase/migrations';
if (!is_dir($migrationsDir)) {
    mkdir($migrationsDir, 0777, true);
}

$outFile = $migrationsDir . '/000001_core_schema.sql';
file_put_contents($outFile, $pgsql);
echo 'Wrote ' . $outFile . ' (' . number_format(strlen($pgsql)) . " bytes)\n";
echo "Tables: " . count($chunks) . "\n";
