#!/usr/bin/env php
<?php
/**
 * Ensure local upload directories exist and are writable (cPanel trial storage).
 * Usage: php scripts/ensure-upload-dirs.php
 */
defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'prod');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../common/config/bootstrap.php';
require __DIR__ . '/../api/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../common/config/main.php',
    require __DIR__ . '/../common/config/main-local.php',
    require __DIR__ . '/../api/config/main.php',
    require __DIR__ . '/../api/config/main-local.php'
);

new yii\web\Application($config);

use common\helpers\DreamlandMediaUrl;
use common\helpers\DreamlandStorageMode;

echo 'Storage mode: ' . DreamlandStorageMode::activeLabel() . "\n";

foreach (DreamlandMediaUrl::localUploadDirs() as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $writable = is_dir($dir) && is_writable($dir);
    echo ($writable ? 'OK' : 'FAIL') . ": {$dir}\n";
    if (!$writable) {
        exit(1);
    }
}

echo "Upload directories ready.\n";
