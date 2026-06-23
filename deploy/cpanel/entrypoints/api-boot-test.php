<?php
/**
 * Yii API boot test — visit /api/boot-test.php then delete this file.
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

$yiiRoot = dirname(__DIR__, 2) . '/dreamland';
require $yiiRoot . '/common/config/load-dotenv.php';
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
    require $yiiRoot . '/deploy/cpanel/config/api-subdir.php',
    is_file($yiiRoot . '/deploy/cpanel/config/db-mysql-fix.php')
        ? require $yiiRoot . '/deploy/cpanel/config/db-mysql-fix.php'
        : []
);

try {
    $app = new yii\web\Application($config);
    echo "Yii API boot: OK\n";
    $app->get('db')->open();
    echo "DB open: OK\n";

    echo 'AWS SDK: ' . (class_exists('Aws\\S3\\S3Client') ? 'OK' : 'MISSING — run composer install') . "\n";

    echo "\n--- health action ---\n";
    $result = $app->runAction('v1/health/index');
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

    $logFile = $yiiRoot . '/api/runtime/logs/app.log';
    if (is_file($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES);
        $tail = array_slice($lines ?: [], -8);
        echo "\n--- api app.log (last lines) ---\n";
        echo implode("\n", $tail) . "\n";
    }
} catch (Throwable $e) {
    echo "FAILED: " . $e->getMessage() . "\n\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
