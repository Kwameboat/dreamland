<?php
/**
 * CLI bootstrap: load ~/dreamland/.env and open a MySQL PDO connection.
 */
declare(strict_types=1);

$yiiRoot = dirname(__DIR__, 2);
require $yiiRoot . '/common/config/load-dotenv.php';

$driver = getenv('DB_DRIVER') ?: 'mysql';
if ($driver !== 'mysql') {
    fwrite(STDERR, "DB_DRIVER must be mysql for cPanel setup (got: {$driver})\n");
    exit(1);
}

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$name = getenv('DB_NAME') ?: '';
$user = getenv('DB_USER') ?: '';
$pass = getenv('DB_PASSWORD') ?: '';

if ($name === '' || $user === '') {
    fwrite(STDERR, "Set DB_NAME and DB_USER in {$yiiRoot}/.env\n");
    exit(1);
}

$dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, 'MySQL connection failed: ' . $e->getMessage() . "\n");
    exit(1);
}

return $pdo;
