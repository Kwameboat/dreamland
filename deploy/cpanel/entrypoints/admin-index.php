<?php
/**
 * cPanel entry: public_html/admin/index.php → Yii backend at /admin
 */
defined('YII_DEBUG') or define('YII_DEBUG', filter_var(getenv('YII_DEBUG') ?: '0', FILTER_VALIDATE_BOOLEAN));
defined('YII_ENV') or define('YII_ENV', getenv('YII_ENV') ?: 'prod');

$yiiRoot = dirname(__DIR__, 2) . '/dreamland';
if (!is_dir($yiiRoot)) {
    http_response_code(500);
    echo 'Dreamland backend not found. Upload the dreamland/ folder next to public_html.';
    exit;
}

require $yiiRoot . '/common/config/load-dotenv.php';
require $yiiRoot . '/common/config/render-https.php';

require $yiiRoot . '/vendor/autoload.php';
require $yiiRoot . '/vendor/yiisoft/yii2/Yii.php';
require $yiiRoot . '/common/config/bootstrap.php';

Yii::setAlias('@webroot', __DIR__);
Yii::setAlias('@web', '/admin');

$config = yii\helpers\ArrayHelper::merge(
    require $yiiRoot . '/common/config/main.php',
    require $yiiRoot . '/common/config/main-local.php',
    require $yiiRoot . '/backend/config/main.php',
    require $yiiRoot . '/backend/config/main-local.php',
    require $yiiRoot . '/deploy/cpanel/config/backend-subdir.php'
);

(new yii\web\Application($config))->run();
