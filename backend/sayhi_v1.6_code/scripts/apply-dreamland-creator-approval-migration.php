<?php
/**
 * Creator approval status for upload / record / go-live gating.
 *
 * Usage: php scripts/apply-dreamland-creator-approval-migration.php
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

if (!columnExists($pdo, 'user', 'dreamland_creator_status')) {
    $pdo->exec("ALTER TABLE `user` ADD COLUMN `dreamland_creator_status` VARCHAR(16) NOT NULL DEFAULT 'none' AFTER `dreamland_account_type`");
    echo "Added user.dreamland_creator_status\n";
}

$pdo->exec("UPDATE `user` SET dreamland_creator_status = 'approved' WHERE dreamland_account_type = 'creator' OR role = 4");
$pdo->exec("UPDATE `user` SET dreamland_creator_status = 'none' WHERE dreamland_account_type = 'viewer' OR role = 3");
$pdo->exec("UPDATE `user` SET dreamland_creator_status = 'approved' WHERE email IN ('creator@dreamland.app')");
$pdo->exec("UPDATE `user` SET dreamland_creator_status = 'none' WHERE email IN ('viewer@dreamland.app')");
echo "Synced demo creator/viewer approval status\n";
echo "Dreamland creator approval migration complete.\n";
