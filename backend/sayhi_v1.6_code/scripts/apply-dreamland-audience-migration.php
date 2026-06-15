<?php
/**
 * Audience targeting columns for broadcasts and notifications.
 *
 * Usage: php scripts/apply-dreamland-audience-migration.php
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

if (!columnExists($pdo, 'broadcast_notification', 'audience_type')) {
    $pdo->exec("ALTER TABLE broadcast_notification ADD COLUMN audience_type VARCHAR(32) NOT NULL DEFAULT 'custom' AFTER message_body");
    echo "Added broadcast_notification.audience_type\n";
}

if (!columnExists($pdo, 'notification', 'audience_group')) {
    $pdo->exec("ALTER TABLE notification ADD COLUMN audience_group VARCHAR(32) NULL DEFAULT NULL AFTER type");
    echo "Added notification.audience_group\n";
}

$pdo->exec("UPDATE user SET dreamland_account_type = 'viewer', role = 3 WHERE email = 'viewer@dreamland.app'");
$pdo->exec("UPDATE user SET dreamland_account_type = 'creator', role = 4 WHERE email = 'creator@dreamland.app'");
echo "Synced demo viewer/creator account types\n";
echo "Dreamland audience migration complete.\n";
