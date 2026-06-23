<?php
/**
 * API diagnostic — visit https://dreamlandgh.app/api/diagnose.php
 * Delete after production is stable.
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

$yiiRoot = dirname(__DIR__, 2) . '/dreamland';
echo "Dreamland API diagnostic\n";
echo str_repeat('=', 40) . "\n\n";

require $yiiRoot . '/common/config/load-dotenv.php';

$pdo = null;
try {
    $driver = getenv('DB_DRIVER') ?: 'mysql';
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: '';
    $user = getenv('DB_USER') ?: '';
    $pass = getenv('DB_PASSWORD') ?: '';
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "database: connected\n";
} catch (Throwable $e) {
    echo 'database FAILED: ' . $e->getMessage() . "\n";
    exit(1);
}

$tables = ['user', 'setting', 'dreamland_settings', 'credit_packages', 'safety_scan_queue', 'post'];
echo "\n--- tables ---\n";
foreach ($tables as $table) {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    $exists = (int) $stmt->fetchColumn() > 0;
    echo ($exists ? 'OK' : 'MISSING') . ": {$table}\n";
}

echo "\n--- Yii API boot ---\n";
try {
    require $yiiRoot . '/common/config/render-https.php';
    require $yiiRoot . '/vendor/autoload.php';
    require $yiiRoot . '/vendor/yiisoft/yii2/Yii.php';
    require $yiiRoot . '/common/config/bootstrap.php';
    require $yiiRoot . '/api/config/bootstrap.php';

    Yii::setAlias('@webroot', __DIR__);
    Yii::setAlias('@web', '/api');

    $config = yii\helpers\ArrayHelper::merge(
        require $yiiRoot . '/common/config/main.php',
        require $yiiRoot . '/common/config/main-local.php',
        require $yiiRoot . '/api/config/main.php',
        require $yiiRoot . '/api/config/main-local.php',
        require $yiiRoot . '/deploy/cpanel/config/api-subdir.php'
    );

    $app = new yii\web\Application($config);
    echo "Yii API boot: OK\n";
    $app->get('db')->open();
    echo "Yii DB open: OK\n";

    echo "\n--- health action ---\n";
    set_time_limit(15);
    $result = $app->runAction('v1/health/index');
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

    $logFile = $yiiRoot . '/api/runtime/logs/app.log';
    if (is_file($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES);
        $tail = array_slice($lines ?: [], -12);
        echo "\n--- api app.log (last lines) ---\n";
        echo implode("\n", $tail) . "\n";
    }
} catch (Throwable $e) {
    echo "Yii FAILED: " . $e->getMessage() . "\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\nDone.\n";
