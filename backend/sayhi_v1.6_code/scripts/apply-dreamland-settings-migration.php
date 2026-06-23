<?php
/**
 * Ensure dreamland_settings has all columns required by admin UI + API.
 * Usage: php scripts/apply-dreamland-settings-migration.php
 */
$pdo = require __DIR__ . '/lib/bootstrap-cli.php';

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
}

if (!tableExists($pdo, 'dreamland_settings')) {
    $pdo->exec("CREATE TABLE `dreamland_settings` (
        `id` SMALLINT NOT NULL DEFAULT 1 PRIMARY KEY,
        `platform_commission_percent` SMALLINT NOT NULL DEFAULT 20,
        `streak_freeze_cost` INT NOT NULL DEFAULT 5,
        `streak_watch_threshold_seconds` INT NOT NULL DEFAULT 300,
        `streak_game_score_threshold` INT NOT NULL DEFAULT 100
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec('INSERT IGNORE INTO dreamland_settings (id) VALUES (1)');
    echo "Created dreamland_settings table\n";
}

$columns = [
    'preview_seconds' => 'TINYINT NOT NULL DEFAULT 3',
    'paystack_public_key' => 'VARCHAR(128) NULL DEFAULT NULL',
    'paystack_secret_key' => 'VARCHAR(128) NULL DEFAULT NULL',
    'vapid_public_key' => 'VARCHAR(255) NULL DEFAULT NULL',
    'vapid_private_key' => 'TEXT NULL DEFAULT NULL',
    'max_reel_duration_seconds' => 'INT NOT NULL DEFAULT 60',
    'max_reel_upload_mb' => 'INT NOT NULL DEFAULT 128',
    'max_live_duration_seconds' => 'INT NOT NULL DEFAULT 0',
];

foreach ($columns as $col => $def) {
    if (!columnExists($pdo, 'dreamland_settings', $col)) {
        $pdo->exec("ALTER TABLE `dreamland_settings` ADD COLUMN `{$col}` {$def}");
        echo "Added dreamland_settings.{$col}\n";
    }
}

$pdo->exec('UPDATE dreamland_settings SET max_live_duration_seconds = 0 WHERE id = 1');
echo "Dreamland settings migration complete.\n";
