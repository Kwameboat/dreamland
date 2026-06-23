<?php
/**
 * Process queued safety scans (PHP worker for cPanel when Node workers are offline).
 * Usage: php scripts/process-safety-queue.php [limit]
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
require $root . '/common/config/bootstrap.php';
require $root . '/api/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require $root . '/common/config/main.php',
    file_exists($root . '/common/config/main-local.php') ? require $root . '/common/config/main-local.php' : [],
    require $root . '/api/config/main.php',
    file_exists($root . '/api/config/main-local.php') ? require $root . '/api/config/main-local.php' : []
);

new yii\web\Application($config);

$limit = isset($argv[1]) ? max(1, (int) $argv[1]) : 25;
if ($limit > 100) {
    $limit = 100;
}

/** @var \common\components\DreamlandSafetyPipeline $pipeline */
$pipeline = Yii::$app->dreamlandSafety;

$stuckPosts = \common\models\Post::find()
    ->where(['appraisal_status' => 'pending_safety'])
    ->orderBy(['id' => SORT_ASC])
    ->limit(20)
    ->all();

$requeued = 0;
foreach ($stuckPosts as $post) {
    $hasQueued = \common\models\SafetyScanQueue::find()
        ->where(['video_id' => (int) $post->id, 'status' => \common\models\SafetyScanQueue::STATUS_QUEUED])
        ->exists();
    if (!$hasQueued) {
        $pipeline->enqueueVideoScan($post);
        $requeued++;
    }
}

$results = $pipeline->processQueuedJobs($limit);
$active = count(array_filter($results, static function ($status) {
    return $status === 'active';
}));
$review = count(array_filter($results, static function ($status) {
    return $status === 'pending_review';
}));
$failed = count(array_filter($results, static function ($status) {
    return $status === 'failed' || $status === 'rejected';
}));

echo "Re-queued {$requeued} stuck post(s).\n";
echo "Processed " . count($results) . " job(s): {$active} active, {$review} pending review, {$failed} failed/rejected.\n";
if ($results !== []) {
    echo implode(', ', $results) . "\n";
}
