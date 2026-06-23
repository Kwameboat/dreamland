<?php
/**
 * Add reel upload limit columns to dreamland_settings (idempotent).
 * Usage: php scripts/apply-dreamland-upload-limits-migration.php
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
    echo "dreamland_settings table missing — run Dreamland base migrations first.\n";
    exit(1);
}

foreach ([
    'max_reel_duration_seconds' => 'INT NOT NULL DEFAULT 60',
    'max_reel_upload_mb' => 'INT NOT NULL DEFAULT 128',
    'max_live_duration_seconds' => 'INT NOT NULL DEFAULT 0',
] as $col => $def) {
    if (!columnExists($pdo, 'dreamland_settings', $col)) {
        $pdo->exec("ALTER TABLE `dreamland_settings` ADD COLUMN `{$col}` {$def}");
        echo "Added dreamland_settings.{$col}\n";
    }
}

$pdo->exec('UPDATE dreamland_settings SET max_live_duration_seconds = 0 WHERE max_live_duration_seconds IS NULL OR max_live_duration_seconds < 0');
echo "Dreamland upload limits migration complete.\n";
