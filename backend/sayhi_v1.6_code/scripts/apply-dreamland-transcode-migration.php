<?php
/**
 * Apply Dreamland reel transcode columns (poster, optimized MP4, HLS paths).
 * Usage: php scripts/apply-dreamland-transcode-migration.php
 */
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'yii2advanced';
$dbUser = getenv('DB_USER') ?: 'yii2advanced';
$dbPass = getenv('DB_PASSWORD') ?: 'secret';

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
$pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

$columns = [
    'optimized_filename' => "ALTER TABLE `post_gallary` ADD COLUMN `optimized_filename` VARCHAR(256) NULL DEFAULT NULL AFTER `video_thumb`",
    'hls_playlist' => "ALTER TABLE `post_gallary` ADD COLUMN `hls_playlist` VARCHAR(512) NULL DEFAULT NULL AFTER `optimized_filename`",
    'transcode_status' => "ALTER TABLE `post_gallary` ADD COLUMN `transcode_status` VARCHAR(32) NOT NULL DEFAULT 'pending' AFTER `hls_playlist`",
];

foreach ($columns as $name => $sql) {
    if (columnExists($pdo, 'post_gallary', $name)) {
        echo "post_gallary.{$name} already exists\n";
        continue;
    }
    $pdo->exec($sql);
    echo "Added post_gallary.{$name}\n";
}

echo "Dreamland transcode migration complete.\n";
