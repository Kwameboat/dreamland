<?php
/**
 * Apply Dreamland v4 engagement schema.
 * Usage: php scripts/apply-dreamland-v4-migration.php
 */
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3309';
$dbName = getenv('DB_NAME') ?: 'yii2advanced';
$dbUser = getenv('DB_USER') ?: 'yii2advanced';
$dbPass = getenv('DB_PASSWORD') ?: 'secret';

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
$pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
}

if (!tableExists($pdo, 'post_watch_events')) {
    $sql = file_get_contents(__DIR__ . '/../doc/db/dreamland_v4_engagement.sql');
    $pdo->exec($sql);
    echo "Created post_watch_events table\n";
} else {
    echo "post_watch_events already exists\n";
}

echo "Dreamland v4 migration complete.\n";
