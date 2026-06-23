<?php
/**
 * Publish a pending_review reel without gamification tables (emergency repair).
 * Usage: php scripts/repair-appraisal-post.php [post_id] [price_credits]
 */
declare(strict_types=1);

$postId = isset($argv[1]) ? (int) $argv[1] : 0;
$priceCredits = isset($argv[2]) ? (int) $argv[2] : 0;

if ($postId <= 0) {
    fwrite(STDERR, "Usage: php scripts/repair-appraisal-post.php [post_id] [price_credits]\n");
    exit(1);
}

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

$post = \api\modules\v1\models\Post::findOne($postId);
if (!$post) {
    fwrite(STDERR, "Post #{$postId} not found.\n");
    exit(1);
}

echo "Post #{$postId}: appraisal_status={$post->appraisal_status}, status={$post->status}, is_paid={$post->is_paid}\n";

try {
    \common\components\DreamlandAppraisalService::approvePost($post, $priceCredits);
    $post->refresh();
    echo "OK: appraisal_status={$post->appraisal_status}, status={$post->status}, price_credits={$post->price_credits}\n";
} catch (\Throwable $e) {
    fwrite(STDERR, 'Failed: ' . $e->getMessage() . "\n");
    exit(1);
}
