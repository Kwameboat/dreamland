<?php
/**
 * Export Dreamland core schema from local MySQL → Supabase PostgreSQL migration files.
 *
 * Prerequisites: dreamland-mysql Docker container running with migrated DB.
 *
 * Usage:
 *   php scripts/export-mysql-to-supabase.php
 *   php scripts/export-mysql-to-supabase.php --apply   # also push to Supabase (needs DATABASE_URL)
 */
require __DIR__ . '/lib/mysql-to-pgsql.php';

$apply = in_array('--apply', $argv ?? [], true);
$dockerContainer = getenv('MYSQL_DOCKER_CONTAINER') ?: 'dreamland-mysql';
$mysqlUser = getenv('DB_USER') ?: 'yii2advanced';
$mysqlPass = getenv('DB_PASSWORD') ?: 'secret';
$mysqlDb = getenv('DB_NAME') ?: 'yii2advanced';

$coreTables = [
    'user', 'post', 'post_gallary', 'post_like', 'post_comment', 'post_view', 'post_share',
    'notification', 'follower', 'user_live_history', 'setting', 'category', 'package', 'payment',
];

$tableList = implode(' ', $coreTables);
if (PHP_OS_FAMILY === 'Windows') {
    $cmd = sprintf(
        'docker exec %s mysqldump -u%s -p%s --no-data --no-tablespaces %s %s',
        $dockerContainer,
        $mysqlUser,
        $mysqlPass,
        $mysqlDb,
        $tableList
    );
} else {
    $cmd = sprintf(
        'docker exec %s mysqldump -u%s -p%s --no-data %s %s 2>/dev/null',
        escapeshellarg($dockerContainer),
        escapeshellarg($mysqlUser),
        escapeshellarg($mysqlPass),
        escapeshellarg($mysqlDb),
        $tableList
    );
}

echo "Exporting schema from MySQL container {$dockerContainer}...\n";
$mysqlDump = shell_exec($cmd);
if (!$mysqlDump || strlen($mysqlDump) < 100) {
    fwrite(STDERR, "Export failed. Is Docker MySQL running? Try: docker start dreamland-mysql\n");
    exit(1);
}

$pgsql = convert_mysql_schema_to_pgsql($mysqlDump);
if (!preg_match('/CREATE TABLE IF NOT EXISTS "user" \(\s*\n\s+id/i', $pgsql)) {
    echo "MySQL export incomplete — building from bundled sayhi_v1_6.sql instead...\n";
    passthru('php ' . escapeshellarg(__DIR__ . '/build-core-schema-from-sayhi.php'), $buildCode);
    exit($buildCode === 0 ? 0 : $buildCode);
}
$migrationsDir = dirname(__DIR__) . '/supabase/migrations';
if (!is_dir($migrationsDir)) {
    mkdir($migrationsDir, 0777, true);
}

$coreFile = $migrationsDir . '/000001_core_schema.sql';
file_put_contents($coreFile, $pgsql);
echo "Wrote " . $coreFile . ' (' . number_format(strlen($pgsql)) . " bytes)\n";

$dreamlandFiles = [
    '000002_dreamland_v1.sql' => dirname(__DIR__) . '/supabase/sql/dreamland_v1_pgsql.sql',
    '000003_dreamland_v2_v4.sql' => dirname(__DIR__) . '/supabase/sql/dreamland_v2_v4_pgsql.sql',
    '000004_dreamland_extensions.sql' => dirname(__DIR__) . '/supabase/sql/dreamland_extensions_pgsql.sql',
    '000005_seed_demo.sql' => dirname(__DIR__) . '/supabase/sql/seed_demo_pgsql.sql',
];

foreach ($dreamlandFiles as $destName => $sourcePath) {
    if (!is_file($sourcePath)) {
        echo "Skip {$destName} (source missing: {$sourcePath})\n";
        continue;
    }
    $dest = $migrationsDir . '/' . $destName;
    copy($sourcePath, $dest);
    echo "Copied {$destName}\n";
}

if ($apply) {
    echo "\nApplying migrations to Supabase...\n";
    passthru('php ' . escapeshellarg(__DIR__ . '/apply-supabase.php'), $code);
    exit($code);
}

echo "\nNext: set DATABASE_URL in .env and run:\n  php scripts/apply-supabase.php\n";
