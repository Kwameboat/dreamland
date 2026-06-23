<?php
/**
 * Creator approval status for upload / record / go-live gating.
 *
 * Usage: php scripts/apply-dreamland-creator-approval-migration.php
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

if (!columnExists($pdo, 'user', 'dreamland_creator_status')) {
    $after = columnExists($pdo, 'user', 'dreamland_account_type') ? ' AFTER `dreamland_account_type`' : '';
    $pdo->exec("ALTER TABLE `user` ADD COLUMN `dreamland_creator_status` VARCHAR(16) NOT NULL DEFAULT 'none'{$after}");
    echo "Added user.dreamland_creator_status\n";
}

$pdo->exec("UPDATE `user` SET dreamland_creator_status = 'approved' WHERE (dreamland_account_type = 'creator' OR role = 4) AND (dreamland_creator_status IS NULL OR dreamland_creator_status = '' OR dreamland_creator_status = 'none')");
$pdo->exec("UPDATE `user` SET dreamland_creator_status = 'none' WHERE (dreamland_account_type = 'viewer' OR role = 3) AND (dreamland_creator_status IS NULL OR dreamland_creator_status = '' OR dreamland_creator_status = 'none')");
$pdo->exec("UPDATE `user` SET dreamland_creator_status = 'approved' WHERE email IN ('creator@dreamland.app')");
$pdo->exec("UPDATE `user` SET dreamland_creator_status = 'none' WHERE email IN ('viewer@dreamland.app')");
echo "Synced demo creator/viewer approval status\n";
echo "Dreamland creator approval migration complete.\n";
