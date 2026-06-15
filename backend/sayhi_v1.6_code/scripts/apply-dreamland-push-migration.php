<?php
/**
 * Web Push subscriptions + VAPID keys for PWA home-screen notifications.
 *
 * Usage: php scripts/apply-dreamland-push-migration.php
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

if (!columnExists($pdo, 'dreamland_settings', 'vapid_public_key')) {
    $pdo->exec('ALTER TABLE dreamland_settings ADD COLUMN vapid_public_key VARCHAR(255) NULL DEFAULT NULL');
    echo "Added dreamland_settings.vapid_public_key\n";
}

if (!columnExists($pdo, 'dreamland_settings', 'vapid_private_key')) {
    $pdo->exec('ALTER TABLE dreamland_settings ADD COLUMN vapid_private_key TEXT NULL DEFAULT NULL');
    echo "Added dreamland_settings.vapid_private_key\n";
}

$pdo->exec("CREATE TABLE IF NOT EXISTS web_push_subscription (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    endpoint TEXT NOT NULL,
    p256dh VARCHAR(255) NOT NULL,
    auth VARCHAR(255) NOT NULL,
    user_agent VARCHAR(512) NULL DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at INT NOT NULL,
    updated_at INT NOT NULL,
    UNIQUE KEY uq_web_push_endpoint (endpoint(255)),
    KEY idx_web_push_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "Ensured web_push_subscription table\n";

$row = $pdo->query('SELECT vapid_public_key, vapid_private_key FROM dreamland_settings WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
$needsKeys = empty($row['vapid_public_key']) || empty($row['vapid_private_key']);

if ($needsKeys) {
    require dirname(__DIR__) . '/vendor/autoload.php';
    $opensslConf = dirname(__DIR__) . '/openssl.cnf';
    if (is_file($opensslConf)) {
        putenv('OPENSSL_CONF=' . $opensslConf);
    }

    $keys = null;
    try {
        $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
    } catch (\Throwable $e) {
        echo "Could not auto-generate VAPID keys: {$e->getMessage()}\n";
        echo "Set DREAMLAND_VAPID_PUBLIC and DREAMLAND_VAPID_PRIVATE env vars, or use Admin → Dreamland Settings → Generate Web Push Keys.\n";
    }

    $publicKey = $keys['publicKey'] ?? getenv('DREAMLAND_VAPID_PUBLIC') ?: null;
    $privateKey = $keys['privateKey'] ?? getenv('DREAMLAND_VAPID_PRIVATE') ?: null;

    if ($publicKey && $privateKey) {
        $stmt = $pdo->prepare(
            'UPDATE dreamland_settings SET vapid_public_key = ?, vapid_private_key = ? WHERE id = 1'
        );
        $stmt->execute([$publicKey, $privateKey]);
        echo "Stored VAPID keys for web push\n";
    }
}

echo "Dreamland push migration complete.\n";
