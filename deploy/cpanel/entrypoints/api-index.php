<?php
/**
 * cPanel entry: public_html/api/index.php → Yii API at /api
 */
defined('YII_DEBUG') or define('YII_DEBUG', filter_var(getenv('YII_DEBUG') ?: '0', FILTER_VALIDATE_BOOLEAN));
defined('YII_ENV') or define('YII_ENV', getenv('YII_ENV') ?: 'prod');

$yiiRoot = dirname(__DIR__, 2) . '/dreamland';
if (!is_dir($yiiRoot)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Dreamland API not found at: {$yiiRoot}\n";
    exit;
}

if (!is_file($yiiRoot . '/vendor/autoload.php')) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Composer vendor/ is missing in {$yiiRoot}\n";
    echo "Run: cd ~/dreamland && composer install --no-dev --optimize-autoloader\n";
    exit;
}

require $yiiRoot . '/common/config/load-dotenv.php';
require $yiiRoot . '/common/config/render-https.php';

putenv('DREAMLAND_YII_ROOT=' . $yiiRoot);
require $yiiRoot . '/api/web/serve-uploads.php';
if (dreamland_try_serve_upload($yiiRoot)) {
    exit;
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: *');
    header('Access-Control-Max-Age: 86400');
    http_response_code(204);
    exit;
}

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

(new yii\web\Application($config))->run();
