<?php
/**
 * Batch transcode existing reel videos (poster + 720p + HLS).
 * Usage: php scripts/transcode-existing-reels.php [limit=10]
 */
declare(strict_types=1);

$limit = isset($argv[1]) ? max(1, (int) $argv[1]) : 10;
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

use api\modules\v1\models\PostGallary;
use common\components\DreamlandVideoProcessor;

if (!DreamlandVideoProcessor::isEnabled()) {
    fwrite(STDERR, "FFmpeg not available. Install ffmpeg or set DREAMLAND_FFMPEG_PATH.\n");
    exit(1);
}

$rows = PostGallary::find()
    ->where(['media_type' => PostGallary::MEDIA_TYPE_VIDEO])
    ->andWhere(['or', ['transcode_status' => null], ['transcode_status' => 'pending'], ['transcode_status' => 'failed']])
    ->orderBy(['id' => SORT_DESC])
    ->limit($limit)
    ->all();

echo 'Transcoding up to ' . $limit . ' reels (' . count($rows) . " queued)\n";

foreach ($rows as $gallery) {
    $filename = (string) $gallery->filename;
    if ($filename === '') {
        continue;
    }
    echo "Post {$gallery->post_id} / {$filename}...\n";
    $result = DreamlandVideoProcessor::processUploadedReel($filename, (int) $gallery->post_id);
    if (!empty($result['poster'])) {
        $gallery->video_thumb = (string) $result['poster'];
    }
    if (!empty($result['optimized'])) {
        $gallery->optimized_filename = (string) $result['optimized'];
    }
    if (!empty($result['hls_playlist'])) {
        $gallery->hls_playlist = (string) $result['hls_playlist'];
    }
    if ((int) ($result['width'] ?? 0) > 0) {
        $gallery->width = (int) $result['width'];
    }
    if ((int) ($result['height'] ?? 0) > 0) {
        $gallery->height = (int) $result['height'];
    }
    $gallery->transcode_status = (string) ($result['status'] ?? DreamlandVideoProcessor::STATUS_FAILED);
    $gallery->save(false);
    echo '  -> ' . $gallery->transcode_status . "\n";
}

echo "Done.\n";
