<?php
/**
 * CLI test for Dreamland video unlock.
 * Usage: php scripts/test-unlock.php [video_id] [viewer_id]
 */
$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';
require $root . '/common/config/bootstrap.php';
require $root . '/api/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require $root . '/common/config/main.php',
    require $root . '/common/config/main-local.php',
    require $root . '/api/config/main.php',
    require $root . '/api/config/main-local.php'
);

new yii\web\Application($config);

$videoId = (int) ($argv[1] ?? 5);
$viewerId = (int) ($argv[2] ?? 3);

echo "Unlock test video_id={$videoId} viewer_id={$viewerId}\n";
$start = microtime(true);

/** @var common\components\DreamlandPaywallService $service */
$service = Yii::$app->dreamlandPaywall;
$result = $service->unlockVideo($viewerId, $videoId);

$elapsed = round((microtime(true) - $start) * 1000);
echo "Done in {$elapsed}ms\n";
echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
