<?php
use yii\grid\GridView;
use yii\helpers\Html;

/** @var yii\data\ActiveDataProvider $pendingPosts */
/** @var yii\data\ActiveDataProvider $queue */
$this->title = 'Dreamland Safety Queue';
?>
<div class="dreamland-admin">
    <h1><?= Html::encode($this->title) ?></h1>
    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="alert alert-success"><?= Html::encode(Yii::$app->session->getFlash('success')) ?></div>
    <?php endif; ?>
    <p>
        <?= Html::a('Open AI Moderation Agent', ['/dreamland-moderation'], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Appraisal Workspace', ['/dreamland-appraisal'], ['class' => 'btn btn-default']) ?>
        <?= Html::beginForm(['process-queue'], 'post', ['style' => 'display:inline-block;margin-left:8px']) ?>
            <?= Html::hiddenInput('limit', 25) ?>
            <?= Html::submitButton('Process safety queue now', ['class' => 'btn btn-success']) ?>
        <?= Html::endForm() ?>
    </p>
    <h3>Pending safety review</h3>
    <?= GridView::widget([
        'dataProvider' => $pendingPosts,
        'columns' => ['id', 'title', 'user_id', 'is_paid', 'created_at:datetime'],
    ]) ?>
    <h3>Scan queue log</h3>
    <?= GridView::widget([
        'dataProvider' => $queue,
        'columns' => [
            'id',
            'video_id',
            'status',
            'result_status',
            [
                'attribute' => 'failure_reason',
                'format' => 'raw',
                'value' => function ($model) {
                    if (!$model->failure_reason) {
                        return '—';
                    }
                    $data = json_decode($model->failure_reason, true);
                    if (is_array($data) && !empty($data['summary'])) {
                        return Html::encode($data['summary']);
                    }
                    return Html::encode(mb_substr($model->failure_reason, 0, 100));
                },
            ],
            'processed_at:datetime',
        ],
    ]) ?>
</div>
