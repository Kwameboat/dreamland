<?php
/**
 * Rejection reasons + creator appeals on post table.
 *
 * Usage: php scripts/apply-dreamland-rejection-migration.php
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

$columns = [
    'rejection_reason' => 'TEXT NULL DEFAULT NULL',
    'rejected_at' => 'INT NULL DEFAULT NULL',
    'rejected_by' => 'INT NULL DEFAULT NULL',
    'appeal_status' => 'VARCHAR(32) NULL DEFAULT NULL',
    'appeal_message' => 'TEXT NULL DEFAULT NULL',
    'appeal_submitted_at' => 'INT NULL DEFAULT NULL',
];

$after = 'appraisal_status';
foreach ($columns as $name => $definition) {
    if (columnExists($pdo, 'post', $name)) {
        echo "Column post.{$name} already exists\n";
        continue;
    }
    $pdo->exec("ALTER TABLE `post` ADD COLUMN `{$name}` {$definition} AFTER `{$after}`");
    echo "Added post.{$name}\n";
    $after = $name;
}

echo "Dreamland rejection/appeal migration complete.\n";
