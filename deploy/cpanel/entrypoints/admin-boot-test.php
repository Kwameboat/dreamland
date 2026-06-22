<?php
/**
 * Yii admin boot test — visit /admin/boot-test.php then delete this file.
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

$yiiRoot = dirname(__DIR__, 2) . '/dreamland';
require $yiiRoot . '/common/config/load-dotenv.php';
require $yiiRoot . '/vendor/autoload.php';
require $yiiRoot . '/vendor/yiisoft/yii2/Yii.php';
require $yiiRoot . '/common/config/bootstrap.php';

Yii::setAlias('@webroot', $yiiRoot . '/backend/web');
Yii::setAlias('@web', '/admin');

$config = yii\helpers\ArrayHelper::merge(
    require $yiiRoot . '/common/config/main.php',
    require $yiiRoot . '/common/config/main-local.php',
    require $yiiRoot . '/backend/config/main.php',
    require $yiiRoot . '/backend/config/main-local.php',
    require $yiiRoot . '/deploy/cpanel/config/backend-subdir.php'
);

try {
    $app = new yii\web\Application($config);
    echo "Yii boot: OK\n";
    $app->get('db')->open();
    echo "DB open: OK\n";
    $users = (int) $app->db->createCommand('SELECT COUNT(*) FROM user')->queryScalar();
    echo "user table rows: {$users}\n";
    $css = Yii::getAlias('@webroot/css/dreamland-admin.css');
    echo 'dreamland-admin.css: ' . (is_file($css) ? 'OK' : "MISSING at {$css}") . "\n";
    $assetsDir = Yii::getAlias('@webroot/assets');
    echo 'assets dir writable: ' . (is_writable($assetsDir) ? 'OK' : 'NO') . "\n";
    backend\assets\AdminLteAsset::register($app->view);
    echo "AdminLteAsset register: OK\n";
    echo "\nIf all OK, login should work at /admin/site/login\n";
} catch (Throwable $e) {
    echo "FAILED: " . $e->getMessage() . "\n\n";
    echo $e->getTraceAsString() . "\n";
}
