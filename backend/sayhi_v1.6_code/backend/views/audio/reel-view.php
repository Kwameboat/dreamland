<?php

use common\helpers\DreamlandMediaUrl;
use yii\helpers\Html;
use common\models\PostGallary;

/* @var $this yii\web\View */
/* @var $postResult common\models\Post */
/* @var $model common\models\Audio|null */

$this->title = 'View Reels';
$this->params['breadcrumbs'][] = ['label' => 'Audio', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$gallery = $postResult->postReelGallary ?? null;
$videoUrl = DreamlandMediaUrl::resolvePostVideoUrl($postResult) ?: ($gallery ? (string) $gallery->filenameUrl : '');
$audioUrl = isset($model) ? (string) ($model->audioUrl ?? '') : '';
$hasAudioTrack = $audioUrl !== '';
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-body">
                <div class="reel-main">
                    <?php if (!$gallery || !$videoUrl): ?>
                        <div class="alert alert-warning">
                            No media file is attached to this reel yet. Re-upload from the creator app or check the safety queue.
                        </div>
                    <?php elseif ((int) $gallery->media_type === PostGallary::MEDIA_TYPE_IMAGE): ?>
                        <?= Html::img($videoUrl, ['alt' => 'Reel image', 'class' => 'reel-image']) ?>
                        <?php if ($hasAudioTrack): ?>
                            <?= Html::tag('audio', '', [
                                'id' => 'audioPlayer',
                                'src' => $audioUrl,
                                'controls' => true,
                                'class' => 'reel-audio-overlay',
                            ]) ?>
                        <?php endif; ?>
                    <?php elseif ((int) $gallery->media_type === PostGallary::MEDIA_TYPE_VIDEO): ?>
                        <video
                            id="videoPlayer"
                            class="reel-video"
                            src="<?= Html::encode($videoUrl) ?>"
                            controls
                            playsinline
                            preload="metadata"
                        ></video>
                        <?php if ($hasAudioTrack): ?>
                            <audio id="audioPlayer" src="<?= Html::encode($audioUrl) ?>" preload="metadata" style="display:none;"></audio>
                            <button id="playButton" type="button" aria-label="Play reel with soundtrack">
                                <i class="fa fa-play-circle" aria-hidden="true"></i>
                            </button>
                        <?php endif; ?>
                        <p class="help-block reel-preview-meta">
                            Preview URL:
                            <a href="<?= Html::encode($videoUrl) ?>" target="_blank" rel="noopener"><?= Html::encode($videoUrl) ?></a>
                        </p>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Unsupported media type for preview (type <?= (int) $gallery->media_type ?>).
                        </div>
                    <?php endif; ?>
                </div>

                <style>
                    .reel-main {
                        position: relative;
                        max-width: 480px;
                        margin: 0 auto;
                    }
                    .reel-video,
                    .reel-image {
                        width: 100%;
                        max-height: 70vh;
                        background: #111;
                        border-radius: 8px;
                    }
                    .reel-audio-overlay {
                        width: 100%;
                        margin-top: 12px;
                    }
                    button#playButton {
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        border-radius: 50%;
                        background: #fffcfc73;
                        width: 50px;
                        height: 50px;
                        border: 0;
                    }
                    button#playButton i.fa {
                        font-size: 40px;
                    }
                    .reel-preview-meta {
                        margin-top: 12px;
                        word-break: break-all;
                    }
                </style>
            </div>
        </div>
    </div>
</div>
<?php if ($gallery && (int) $gallery->media_type === PostGallary::MEDIA_TYPE_VIDEO && $hasAudioTrack): ?>
<?php
$audioStartTime = (float) ($postResult->audio_start_time ?? 0);
$audioStopTime = (float) ($postResult->audio_end_time ?? 0);
$js = <<<JS
$(document).ready(function() {
    var video = document.getElementById("videoPlayer");
    var audio = document.getElementById("audioPlayer");
    var playButton = document.getElementById("playButton");
    if (!video || !audio || !playButton) {
        return;
    }

    var audioStartTime = {$audioStartTime};
    var audioStopTime = {$audioStopTime};

    playButton.addEventListener("click", function() {
        if (video.paused && audio.paused) {
            video.currentTime = 0;
            if (audioStartTime > 0) {
                audio.currentTime = audioStartTime;
            }
            if (audioStopTime > audioStartTime) {
                setTimeout(function() {
                    video.pause();
                    audio.pause();
                    playButton.innerHTML = '<i class="fa fa-play-circle" aria-hidden="true"></i>';
                }, (audioStopTime - audioStartTime) * 1000);
            }
            video.play();
            audio.play();
            playButton.innerHTML = '<i class="fa fa-pause-circle" aria-hidden="true"></i>';
        } else {
            video.pause();
            audio.pause();
            playButton.innerHTML = '<i class="fa fa-play-circle" aria-hidden="true"></i>';
        }
    });
});
JS;
$this->registerJs($js, \yii\web\View::POS_READY);
?>
<?php endif; ?>
