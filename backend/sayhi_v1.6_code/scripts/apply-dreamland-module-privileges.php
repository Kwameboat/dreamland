<?php
/**
 * Ensure module_auth tables exist and Dreamland module privileges are registered.
 * Usage: php scripts/apply-dreamland-module-privileges.php
 */
$pdo = require __DIR__ . '/lib/bootstrap-cli.php';

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
}

if (!tableExists($pdo, 'module_auth')) {
    $pdo->exec("CREATE TABLE `module_auth` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(256) NULL,
        `alias` VARCHAR(256) NULL,
        `level` INT NOT NULL DEFAULT 1,
        `parent_id` INT NULL,
        `action_list` TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created module_auth\n";
}

if (!tableExists($pdo, 'module_auth_user')) {
    $pdo->exec("CREATE TABLE `module_auth_user` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `module_auth_id` INT NOT NULL,
        `is_enabled` INT NOT NULL DEFAULT 1,
        KEY `idx_module_auth_user_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created module_auth_user\n";
}

$baseModules = [
    [1, 'Administrator', 'administrator'],
    [2, 'User', 'user'],
    [3, 'Post', 'post'],
    [4, 'Competition', 'competition'],
    [5, 'Club', 'club'],
    [6, 'Support Request', 'supportRequest'],
    [7, 'Payment', 'payment'],
    [8, 'Package', 'package'],
    [9, 'Tv Channel', 'tvChannel'],
    [10, 'Podcast', 'podcast'],
    [11, 'Gift', 'gift'],
    [12, 'Faq', 'faq'],
    [13, 'Organization', 'organization'],
    [14, 'Event', 'event'],
    [15, 'Fund Raising', 'fundRaising'],
    [16, 'Reel', 'reel'],
    [17, 'Poll', 'poll'],
    [18, 'Broadcast Notifications', 'broadcastNotifications'],
    [19, 'Coupon', 'coupon'],
    [20, 'Dating', 'dating'],
    [21, 'Story', 'story'],
    [22, 'Job', 'job'],
    [23, 'Ad', 'ad'],
    [24, 'Report', 'report'],
    [25, 'Setting', 'setting'],
    [26, 'Live History', 'liveHistory'],
    [27, 'Post Promotion', 'promotion'],
    [28, 'Dreamland Appraisal', 'dreamlandAppraisal'],
    [29, 'Dreamland AI Moderation', 'dreamlandModeration'],
    [30, 'Dreamland Safety Queue', 'dreamlandSafety'],
    [31, 'Dreamland Platform Settings', 'dreamlandSettings'],
    [32, 'Credit Packages', 'creditPackage'],
];

$upsert = $pdo->prepare(
    'INSERT INTO module_auth (id, name, alias, level, parent_id, action_list)
     VALUES (?, ?, ?, 1, NULL, NULL)
     ON DUPLICATE KEY UPDATE name = VALUES(name), alias = VALUES(alias), level = 1'
);

foreach ($baseModules as [$id, $name, $alias]) {
    $upsert->execute([$id, $name, $alias]);
    echo "Module: {$name} ({$alias})\n";
}

echo "Dreamland module privileges migration complete.\n";
