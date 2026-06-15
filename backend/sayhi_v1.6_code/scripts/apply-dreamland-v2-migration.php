<?php
/**
 * Apply Dreamland v2 creator/viewer schema updates.
 * Usage: php scripts/apply-dreamland-v2-migration.php
 */
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3309';
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

if (!columnExists($pdo, 'user', 'dreamland_account_type')) {
    $pdo->exec("ALTER TABLE `user` ADD COLUMN `dreamland_account_type` ENUM('viewer','creator') NOT NULL DEFAULT 'viewer' AFTER `role`");
    echo "Added user.dreamland_account_type\n";
}

foreach ([
    'live_title' => "ADD COLUMN `live_title` VARCHAR(255) NULL DEFAULT NULL AFTER `token`",
    'is_monetized' => "ADD COLUMN `is_monetized` TINYINT(1) NOT NULL DEFAULT 0 AFTER `live_title`",
    'price_credits' => "ADD COLUMN `price_credits` INT NULL DEFAULT NULL AFTER `is_monetized`",
    'total_comment' => "ADD COLUMN `total_comment` INT NOT NULL DEFAULT 0 AFTER `price_credits`",
] as $col => $sql) {
    if (!columnExists($pdo, 'user_live_history', $col)) {
        $pdo->exec("ALTER TABLE `user_live_history` {$sql}");
        echo "Added user_live_history.{$col}\n";
    }
}

$pdo->exec("UPDATE `user` SET `dreamland_account_type` = 'creator', `role` = 4 WHERE `email` = 'creator@dreamland.app'");
$pdo->exec("UPDATE `user` SET `dreamland_account_type` = 'viewer', `role` = 3 WHERE `email` = 'viewer@dreamland.app'");
echo "Dreamland v2 migration complete.\n";
echo "Run php scripts/apply-dreamland-v3-migration.php for live unlock support.\n";
