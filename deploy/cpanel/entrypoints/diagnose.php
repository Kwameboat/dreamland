<?php
/**
 * cPanel diagnostic — visit https://yourdomain.com/admin/diagnose.php
 * Remove this file after deployment is working.
 */
header('Content-Type: text/plain; charset=utf-8');

echo "Dreamland cPanel diagnostic\n";
echo str_repeat('=', 40) . "\n\n";

echo 'PHP version: ' . PHP_VERSION . "\n";
echo 'Server: ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "\n\n";

$yiiRoot = dirname(__DIR__, 2) . '/dreamland';
echo "Expected Yii root: {$yiiRoot}\n";
echo 'dreamland folder: ' . (is_dir($yiiRoot) ? 'OK' : 'MISSING') . "\n";
echo 'vendor/autoload.php: ' . (is_file($yiiRoot . '/vendor/autoload.php') ? 'OK' : 'MISSING — run composer install in ~/dreamland') . "\n";
echo '.env file: ' . (is_file($yiiRoot . '/.env') ? 'OK' : 'MISSING') . "\n";
echo 'params-local.php: ' . (is_file($yiiRoot . '/common/config/params-local.php') ? 'OK' : 'MISSING') . "\n";
echo 'backend-subdir.php: ' . (is_file($yiiRoot . '/deploy/cpanel/config/backend-subdir.php') ? 'OK' : 'MISSING') . "\n\n";

$runtimeDirs = [
    'api/runtime',
    'backend/runtime',
    'common/runtime',
];
foreach ($runtimeDirs as $dir) {
    $path = $yiiRoot . '/' . $dir;
    $writable = is_dir($path) && is_writable($path);
    echo "{$dir}: " . ($writable ? 'writable' : 'NOT writable — chmod 775') . "\n";
}

if (is_file($yiiRoot . '/common/config/load-dotenv.php')) {
    require $yiiRoot . '/common/config/load-dotenv.php';
}

echo "\n--- .env keys (values hidden) ---\n";
$envKeys = ['DB_DRIVER', 'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'COOKIE_VALIDATION_KEY', 'YII_ENV'];
foreach ($envKeys as $key) {
    $val = getenv($key);
    if ($val === false || $val === '') {
        echo "{$key}: NOT SET\n";
    } elseif ($key === 'COOKIE_VALIDATION_KEY') {
        echo "{$key}: set (" . strlen($val) . " chars)\n";
    } elseif (str_contains($key, 'PASSWORD') || str_contains($key, 'SECRET')) {
        echo "{$key}: set (hidden)\n";
    } else {
        echo "{$key}: {$val}\n";
    }
}

if (is_file($yiiRoot . '/vendor/autoload.php')) {
    echo "\n--- Composer autoload ---\n";
    try {
        require $yiiRoot . '/vendor/autoload.php';
        echo "autoload: OK\n";
    } catch (Throwable $e) {
        echo 'autoload FAILED: ' . $e->getMessage() . "\n";
    }
}

if (is_file($yiiRoot . '/vendor/autoload.php') && is_file($yiiRoot . '/common/config/main-local.php')) {
    echo "\n--- Database ---\n";
    try {
        require $yiiRoot . '/vendor/yiisoft/yii2/Yii.php';
        $params = array_merge(
            require $yiiRoot . '/common/config/params.php',
            require $yiiRoot . '/common/config/params-local.php'
        );
        $db = $params['db'] ?? [];
        $driver = $db['driver'] ?? 'mysql';
        $host = $db['host'] ?? '127.0.0.1';
        $port = $db['port'] ?? ($driver === 'pgsql' ? 5432 : 3306);
        $name = $db['name'] ?? '';
        $user = $db['username'] ?? '';
        $pass = $db['password'] ?? '';
        if ($driver === 'pgsql') {
            $dsn = "pgsql:host={$host};port={$port};dbname={$name}";
        } else {
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        }
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "database: connected ({$driver})\n";
    } catch (Throwable $e) {
        echo 'database FAILED: ' . $e->getMessage() . "\n";
    }
}

echo "\n--- PHP error log (last 15 lines) ---\n";
$logCandidates = [
    dirname(__DIR__, 2) . '/logs/error_log',
    dirname(__DIR__, 2) . '/public_html/error_log',
    '/home/' . (get_current_user() ?: '') . '/logs/error_log',
];
$shown = false;
foreach ($logCandidates as $log) {
    if (is_file($log) && is_readable($log)) {
        $lines = file($log, FILE_IGNORE_NEW_LINES);
        $tail = array_slice($lines ?: [], -15);
        echo "({$log})\n";
        echo implode("\n", $tail) . "\n";
        $shown = true;
        break;
    }
}
if (!$shown) {
    echo "No error_log found in common locations.\n";
}

echo "\nDone. Fix MISSING items above, then reload /admin/site/login\n";
