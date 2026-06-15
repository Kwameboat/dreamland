<?php
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var bool $agentHealthy */
/** @var array|null $agentConfig */
/** @var yii\data\ActiveDataProvider $queue */
/** @var yii\data\ActiveDataProvider $flagged */
/** @var array|null $testResult */

$this->title = 'AI Moderation Agent';
$testResult = Yii::$app->session->get('moderation_test_result');
if ($testResult) {
    Yii::$app->session->remove('moderation_test_result');
}
?>
<div class="dreamland-admin dreamland-moderation-index">
    <h1><?= Html::encode($this->title) ?></h1>
    <p class="text-muted">Dreamland AI powered by <strong>Google Gemini</strong> (multimodal) + Ghana lexicons — Twi, Ga, Ewe, Hausa, Dagbani, Pidgin &amp; English.</p>

    <div class="row">
        <div class="col-md-4">
            <div class="box box-<?= $agentHealthy ? 'success' : 'danger' ?>">
                <div class="box-header with-border"><h3 class="box-title">Agent status</h3></div>
                <div class="box-body">
                    <p><strong><?= $agentHealthy ? 'Online' : 'Offline' ?></strong></p>
                    <?php if ($agentConfig): ?>
                        <p class="small">Block score: <?= (int) ($agentConfig['blockThreshold'] ?? 70) ?> · Review: <?= (int) ($agentConfig['reviewThreshold'] ?? 40) ?></p>
                        <?php $gemini = $agentConfig['gemini'] ?? []; ?>
                        <p class="small">Gemini: <?= !empty($gemini['configured']) ? Html::encode('Online · ' . ($gemini['model'] ?? 'gemini')) : 'Not configured — set GEMINI_API_KEY in moderation-agent/.env' ?></p>
                        <p class="small">Languages: <?= Html::encode(implode(', ', array_column($agentConfig['locales'] ?? [], 'label'))) ?></p>
                    <?php else: ?>
                        <p class="text-warning">Start agent: <code>.\start-moderation-agent.ps1</code></p>
                        <p class="small">Add <code>GEMINI_API_KEY</code> to <code>dreamland/moderation-agent/.env</code></p>
                    <?php endif; ?>
                    <?= Html::a('Manage keywords', ['keywords'], ['class' => 'btn btn-default btn-sm']) ?>
                    <?= Html::a('Safety queue', ['/dreamland-safety'], ['class' => 'btn btn-default btn-sm']) ?>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border"><h3 class="box-title">Test moderation</h3></div>
                <div class="box-body">
                    <?php $form = ActiveForm::begin(['action' => ['test'], 'method' => 'post']); ?>
                    <div class="form-group">
                        <label>Sample title / caption (any Ghana language)</label>
                        <textarea name="text" class="form-control" rows="3" placeholder="e.g. Twi insult, momo scam, pidgin harassment…"></textarea>
                    </div>
                    <?= Html::submitButton('Run AI scan', ['class' => 'btn btn-primary']) ?>
                    <?php ActiveForm::end(); ?>

                    <?php if ($testResult): ?>
                        <hr>
                        <p><strong>Decision:</strong>
                            <span class="label label-<?= ($testResult['decision'] ?? '') === 'allow' ? 'success' : (($testResult['decision'] ?? '') === 'review' ? 'warning' : 'danger') ?>">
                                <?= Html::encode(strtoupper($testResult['decision'] ?? 'unknown')) ?>
                            </span>
                            · Score: <?= (int) ($testResult['score'] ?? 0) ?>
                        </p>
                        <p><?= Html::encode($testResult['summary'] ?? '') ?></p>
                        <?php if (!empty($testResult['matches'])): ?>
                            <ul class="small">
                                <?php foreach ($testResult['matches'] as $m): ?>
                                    <li><?= Html::encode(($m['categoryLabel'] ?? '') . ' — ' . ($m['localeLabel'] ?? '') . ': "' . ($m['term'] ?? '') . '"') ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <h3>Flagged / pending content</h3>
    <?= GridView::widget([
        'dataProvider' => $flagged,
        'columns' => [
            'id',
            'title',
            'appraisal_status',
            [
                'label' => 'Appeal',
                'attribute' => 'appeal_status',
                'value' => function ($model) {
                    if (empty($model->appeal_status)) {
                        return '—';
                    }
                    $status = Html::encode($model->appeal_status);
                    $msg = trim((string) ($model->appeal_message ?? ''));
                    if ($msg === '') {
                        return $status;
                    }
                    return $status . Html::tag('div', Html::encode(mb_substr($msg, 0, 120)), ['class' => 'small text-muted']);
                },
                'format' => 'raw',
            ],
            'is_paid',
            'created_at:datetime',
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{preview} {approve} {review} {reject}',
                'buttons' => [
                    'approve' => function ($url, $model) {
                        return Html::beginForm(['decide', 'id' => $model->id], 'post', ['style' => 'display:inline'])
                            . Html::hiddenInput('decision', 'approve')
                            . Html::submitButton('Approve', ['class' => 'btn btn-success btn-xs'])
                            . Html::endForm();
                    },
                    'preview' => function ($url, $model) {
                        return Html::a('Preview', ['/audio/reel-view', 'id' => $model->id], [
                            'class' => 'btn btn-default btn-xs',
                            'target' => '_blank',
                            'rel' => 'noopener',
                        ]);
                    },
                    'review' => function ($url, $model) {
                        return Html::beginForm(['decide', 'id' => $model->id], 'post', ['style' => 'display:inline'])
                            . Html::hiddenInput('decision', 'review')
                            . Html::submitButton('Appraisal', ['class' => 'btn btn-warning btn-xs'])
                            . Html::endForm();
                    },
                    'reject' => function ($url, $model) {
                        return Html::beginForm(['decide', 'id' => $model->id], 'post', ['class' => 'dl-reject-form'])
                            . Html::hiddenInput('decision', 'reject')
                            . Html::textarea('rejection_reason', '', [
                                'class' => 'form-control input-sm',
                                'rows' => 2,
                                'placeholder' => 'Reason for rejection (required)',
                                'required' => true,
                                'style' => 'width:200px;margin-bottom:4px;',
                            ])
                            . Html::submitButton('Reject & notify', ['class' => 'btn btn-danger btn-xs'])
                            . Html::endForm();
                    },
                ],
            ],
        ],
    ]) ?>

    <h3>Scan queue (AI agent log)</h3>
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
                        return '<span class="text-muted">—</span>';
                    }
                    $data = json_decode($model->failure_reason, true);
                    if (is_array($data) && !empty($data['summary'])) {
                        return Html::encode($data['summary']);
                    }
                    return Html::encode(mb_substr($model->failure_reason, 0, 80));
                },
            ],
            'processed_at:datetime',
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{requeue}',
                'buttons' => [
                    'requeue' => function ($url, $model) {
                        if ($model->status === 'queued') {
                            return '';
                        }
                        return Html::beginForm(['requeue', 'id' => $model->id], 'post')
                            . Html::submitButton('Re-run AI', ['class' => 'btn btn-default btn-xs'])
                            . Html::endForm();
                    },
                ],
            ],
        ],
    ]) ?>
</div>
