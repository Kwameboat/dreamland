<?php
/**
 * Ensure Paystack wallet tables exist for credit top-ups.
 * Usage: php scripts/apply-dreamland-paystack-migration.php
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

if (!tableExists($pdo, 'credit_packages')) {
    $pdo->exec("CREATE TABLE `credit_packages` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `credit_amount` INT NOT NULL DEFAULT 0,
        `fiat_cost` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `currency` VARCHAR(8) NOT NULL DEFAULT 'GHS',
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created credit_packages\n";
}

if (!tableExists($pdo, 'credit_package_transactions')) {
    $pdo->exec("CREATE TABLE `credit_package_transactions` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `credit_package_id` INT NOT NULL,
        `paystack_reference` VARCHAR(128) NOT NULL,
        `amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `currency` VARCHAR(8) NOT NULL DEFAULT 'GHS',
        `credits_to_grant` INT NOT NULL DEFAULT 0,
        `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
        `completed_at` DATETIME NULL,
        `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_credit_pkg_tx_reference` (`paystack_reference`),
        KEY `idx_credit_pkg_tx_user` (`user_id`),
        KEY `idx_credit_pkg_tx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created credit_package_transactions\n";
}

$count = (int) $pdo->query('SELECT COUNT(*) FROM credit_packages WHERE is_active = 1')->fetchColumn();
if ($count === 0) {
    $seed = $pdo->prepare(
        'INSERT INTO credit_packages (id, credit_amount, fiat_cost, currency, is_active, created_at)
         VALUES (?, ?, ?, ?, 1, NOW())
         ON DUPLICATE KEY UPDATE credit_amount = VALUES(credit_amount), fiat_cost = VALUES(fiat_cost), is_active = 1'
    );
    $packages = [
        [1, 50, 5.00, 'GHS'],
        [2, 120, 10.00, 'GHS'],
        [3, 300, 25.00, 'GHS'],
    ];
    foreach ($packages as [$id, $credits, $cost, $currency]) {
        $seed->execute([$id, $credits, $cost, $currency]);
        echo "Seeded package {$id}: {$credits} credits\n";
    }
}

echo "Dreamland Paystack wallet migration complete.\n";
