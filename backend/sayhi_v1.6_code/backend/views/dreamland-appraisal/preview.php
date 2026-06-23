<?php

use common\helpers\DreamlandMediaUrl;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var api\modules\v1\models\Post $post */

$this->title = 'Preview reel #' . (int) $post->id;
$this->params['breadcrumbs'][] = ['label' => 'Appraisal Workspace', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$videoUrl = DreamlandMediaUrl::resolvePostVideoUrl($post) ?: '';
$isPremium = (int) $post->is_paid === 1;
?>
<div class="dreamland-appraisal-preview box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><?= Html::encode(strip_tags((string) $post->title)) ?></h3>
        <p class="text-muted">
            Post #<?= (int) $post->id ?>
            · Creator #<?= (int) $post->user_id ?>
            · <?= $isPremium ? 'Premium' : 'Free' ?>
            · <?= Html::encode((string) $post->appraisal_status) ?>
        </p>
    </div>
    <div class="box-body">
        <?php if ($videoUrl === ''): ?>
            <div class="alert alert-warning">
                No playable video file found for this reel. Check upload storage or re-queue the safety scan.
            </div>
        <?php else: ?>
            <div class="dl-appraisal-preview-player">
                <video
                    class="dl-appraisal-preview-video"
                    src="<?= Html::encode($videoUrl) ?>"
                    controls
                    playsinline
                    preload="metadata"
                ></video>
            </div>
            <p class="help-block" style="margin-top:12px;word-break:break-all;">
                Stream URL:
                <a href="<?= Html::encode($videoUrl) ?>" target="_blank" rel="noopener"><?= Html::encode($videoUrl) ?></a>
            </p>
        <?php endif; ?>
        <p>
            <?= Html::a('Back to appraisal queue', ['index'], ['class' => 'btn btn-default']) ?>
        </p>
    </div>
</div>
<style>
.dl-appraisal-preview-player {
    max-width: 480px;
    margin: 0 auto;
}
.dl-appraisal-preview-video {
    width: 100%;
    max-height: 70vh;
    background: #111;
    border-radius: 8px;
}
</style>
