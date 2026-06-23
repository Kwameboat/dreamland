#!/usr/bin/env php
<?php
/**
 * Locate reel video files on disk and verify public URLs.
 * Usage: php scripts/find-reel-video.php [post_id]
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

use api\modules\v1\models\Post;
use common\helpers\DreamlandMediaUrl;
use common\helpers\DreamlandWasabiStorage;

$postId = isset($argv[1]) ? (int) $argv[1] : 0;
$query = Post::find()->where(['type' => Post::TYPE_REEL])->orderBy(['id' => SORT_DESC]);
if ($postId > 0) {
    $query->andWhere(['id' => $postId]);
}
$posts = $query->limit($postId > 0 ? 1 : 20)->all();

if (!$posts) {
    echo "No reel posts found.\n";
    exit(1);
}

foreach ($posts as $post) {
    echo "Post #{$post->id} — {$post->title}\n";
    foreach (DreamlandMediaUrl::filenameCandidatesForPost($post) as $filename) {
        $local = DreamlandMediaUrl::localFileExists($filename);
        $wasabi = DreamlandWasabiStorage::isConfigured()
            && DreamlandWasabiStorage::objectExists('image', $filename);
        $url = DreamlandMediaUrl::fileUrlForPostFilename($filename);
        echo "  {$filename}\n";
        echo "    local: " . ($local ? 'yes' : 'no') . "\n";
        echo "    wasabi: " . ($wasabi ? 'yes' : 'no') . "\n";
        echo "    url: {$url}\n";
    }
    echo "\n";
}

echo "Upload dirs:\n";
foreach (DreamlandMediaUrl::localUploadDirs() as $dir) {
    $count = 0;
    if (is_dir($dir)) {
        foreach (glob($dir . '/*.{mp4,mov,webm,m4v}', GLOB_BRACE) ?: [] as $file) {
            $count++;
        }
    }
    echo "  {$dir} — " . (is_dir($dir) ? "{$count} videos" : 'missing') . "\n";
}
