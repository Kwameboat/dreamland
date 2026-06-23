<?php
/**
 * Repair missing post_gallary rows for reels (restores PWA video playback).
 * Usage: php scripts/repair-reel-gallery.php [post_id]
 */
declare(strict_types=1);

$postId = isset($argv[1]) ? (int) $argv[1] : 0;

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

use api\modules\v1\models\Post;
use api\modules\v1\models\PostGallary;
use common\helpers\DreamlandMediaUrl;

$query = Post::find()->where(['type' => Post::TYPE_REEL, 'status' => Post::STATUS_ACTIVE]);
if ($postId > 0) {
    $query->andWhere(['id' => $postId]);
}

$posts = $query->orderBy(['id' => SORT_DESC])->limit($postId > 0 ? 1 : 50)->all();
if (!$posts) {
    fwrite(STDERR, "No reel posts found.\n");
    exit(1);
}

$fixed = 0;
foreach ($posts as $post) {
    $hasVideo = PostGallary::find()
        ->where(['post_id' => (int) $post->id, 'media_type' => PostGallary::MEDIA_TYPE_VIDEO])
        ->exists();

    $candidates = DreamlandMediaUrl::filenameCandidatesForPost($post);
    $filename = null;
    foreach ($candidates as $candidate) {
        if (DreamlandMediaUrl::localFileExists($candidate)) {
            $filename = $candidate;
            break;
        }
    }
    if ($filename === null && $candidates !== []) {
        $filename = $candidates[0];
    }

    if ($filename === null) {
        echo "Post #{$post->id}: no filename candidate found.\n";
        continue;
    }

    if (!$hasVideo) {
        $gallery = new PostGallary();
        $gallery->post_id = (int) $post->id;
        $gallery->type = 1;
        $gallery->media_type = PostGallary::MEDIA_TYPE_VIDEO;
        $gallery->filename = $filename;
        $gallery->video_thumb = '';
        $gallery->is_default = PostGallary::IS_DEFAULT_YES;
        $gallery->status = PostGallary::STATUS_ACTIVE;
        $gallery->created_at = time();
        $gallery->width = 0;
        $gallery->height = 0;
        $gallery->save(false);
        echo "Post #{$post->id}: inserted gallery row ({$filename}).\n";
        $fixed++;
    } else {
        PostGallary::updateAll(
            ['filename' => $filename, 'status' => PostGallary::STATUS_ACTIVE],
            ['post_id' => (int) $post->id, 'media_type' => PostGallary::MEDIA_TYPE_VIDEO]
        );
        echo "Post #{$post->id}: updated gallery filename to {$filename}.\n";
        $fixed++;
    }

    $url = DreamlandMediaUrl::resolvePostVideoUrl($post);
    echo "  reel_video_url: {$url}\n";
    echo "  local_exists: " . (DreamlandMediaUrl::localFileExists($filename) ? 'yes' : 'no') . "\n";
}

echo $fixed > 0 ? "Repaired {$fixed} reel(s).\n" : "Nothing to repair.\n";
