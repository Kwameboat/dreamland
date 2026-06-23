<?php

use yii\grid\GridView;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Appraisal Workspace';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="dreamland-appraisal-index box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Dreamland Appraisal Workspace</h3>
        <p class="text-muted">Review premium uploads, set credit price, approve or reject.</p>
    </div>
    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="alert alert-success"><?= Html::encode(Yii::$app->session->getFlash('success')) ?></div>
    <?php endif; ?>
    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="alert alert-danger"><?= Html::encode(Yii::$app->session->getFlash('error')) ?></div>
    <?php endif; ?>
    <div class="box-body table-responsive">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => [
                'id',
                [
                    'attribute' => 'title',
                    'value' => function ($model) {
                        return strip_tags((string) $model->title);
                    },
                ],
                'user_id',
                'created_at:datetime',
                [
                    'format' => 'raw',
                    'label' => 'Evaluate',
                    'value' => function ($model) {
                        return Html::beginForm(['evaluate', 'id' => $model->id], 'post', ['class' => 'form-inline dl-appraisal-form'])
                            . Html::input('number', 'price_credits', '', ['class' => 'form-control', 'placeholder' => 'Credits', 'min' => 1, 'style' => 'width:100px;margin-right:8px;'])
                            . Html::submitButton('Approve', ['class' => 'btn btn-success btn-sm', 'name' => 'status', 'value' => 'active'])
                            . '<div style="margin-top:8px;width:100%;">'
                            . Html::textarea('rejection_reason', '', [
                                'class' => 'form-control input-sm',
                                'rows' => 2,
                                'placeholder' => 'Rejection reason (required if rejecting)',
                                'style' => 'width:220px;margin-right:8px;display:inline-block;vertical-align:top;',
                            ])
                            . Html::submitButton('Reject & notify', ['class' => 'btn btn-danger btn-sm', 'name' => 'status', 'value' => 'rejected'])
                            . '</div>'
                            . Html::endForm();
                    },
                ],
            ],
        ]) ?>
    </div>
</div>
