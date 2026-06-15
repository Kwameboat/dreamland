<?php
/**
 * Generate VAPID keys and store in dreamland_settings (no key output).
 * Usage: php scripts/generate-vapid-keys.php
 */
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3309';
$dbName = getenv('DB_NAME') ?: 'yii2advanced';
$dbUser = getenv('DB_USER') ?: 'yii2advanced';
$dbPass = getenv('DB_PASSWORD') ?: 'secret';

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
$pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

require dirname(__DIR__) . '/vendor/autoload.php';

$opensslConf = dirname(__DIR__) . '/openssl.cnf';
if (is_file($opensslConf)) {
    putenv('OPENSSL_CONF=' . $opensslConf);
}

$publicKey = getenv('DREAMLAND_VAPID_PUBLIC') ?: null;
$privateKey = getenv('DREAMLAND_VAPID_PRIVATE') ?: null;

if (!$publicKey || !$privateKey) {
    try {
        $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
        $publicKey = $keys['publicKey'];
        $privateKey = $keys['privateKey'];
    } catch (Throwable $e) {
        fwrite(STDERR, "VAPID generation failed: {$e->getMessage()}\n");
        fwrite(STDERR, "Set DREAMLAND_VAPID_PUBLIC and DREAMLAND_VAPID_PRIVATE environment variables.\n");
        exit(1);
    }
}

$stmt = $pdo->prepare('UPDATE dreamland_settings SET vapid_public_key = ?, vapid_private_key = ? WHERE id = 1');
$stmt->execute([$publicKey, $privateKey]);
echo "VAPID keys stored in dreamland_settings.\n";
