<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
/** @var common\models\DreamlandSetting $model */
$this->title = 'Dreamland Settings';
$model->max_reel_duration_minutes = $model->max_reel_duration_minutes ?: max(1, (int) round(((int) $model->max_reel_duration_seconds) / 60));
?>
<div class="dreamland-admin">
    <h1><?= Html::encode($this->title) ?></h1>
    <?php $form = ActiveForm::begin(); ?>
    <?= $form->field($model, 'platform_commission_percent')->input('number', ['min' => 0, 'max' => 100]) ?>
    <?= $form->field($model, 'preview_seconds')->input('number', ['min' => 1, 'max' => 30]) ?>
    <?= $form->field($model, 'streak_freeze_cost')->input('number', ['min' => 1]) ?>
    <?= $form->field($model, 'streak_watch_threshold_seconds')->input('number', ['min' => 60]) ?>
    <?= $form->field($model, 'streak_game_score_threshold')->input('number', ['min' => 1]) ?>
    <div class="panel panel-default">
        <div class="panel-heading"><h4>Creator upload limits</h4></div>
        <div class="panel-body">
            <?= $form->field($model, 'max_reel_duration_minutes')->input('number', ['min' => 1, 'max' => 10])
                ->hint('Maximum length for uploaded or recorded reels (e.g. 1 = only clips up to 1 minute). Applies to Studio uploads and in-app recording — not live broadcasts.') ?>
            <?= $form->field($model, 'max_reel_upload_mb')->input('number', ['min' => 1, 'max' => 512])
                ->hint('Maximum reel file size in megabytes.') ?>
            <p class="help-block text-muted" style="margin-top:8px;">
                <strong>Live broadcasts</strong> are not time-limited. Creators start and end live manually in the PWA.
            </p>
        </div>
    </div>
    <?= $form->field($model, 'paystack_public_key')->textInput() ?>
    <?= $form->field($model, 'paystack_secret_key')->passwordInput() ?>
    <?php if (!empty($model->vapid_public_key)) { ?>
        <div class="form-group">
            <label class="control-label">Web Push (PWA) public key</label>
            <p class="help-block"><code><?= Html::encode($model->vapid_public_key) ?></code></p>
            <p class="text-muted">Used when users install Dreamland to their home screen.</p>
        </div>
    <?php } else { ?>
        <div class="form-group">
            <?= Html::submitButton('Generate Web Push Keys', ['class' => 'btn btn-warning', 'name' => 'generate_vapid', 'value' => '1']) ?>
            <p class="help-block text-muted">Required for PWA notifications on home-screen installs.</p>
        </div>
    <?php } ?>
    <div class="form-group">
        <?= Html::submitButton('Save settings', ['class' => 'btn btn-primary']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>
