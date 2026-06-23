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
        <p class="text-muted">Review moderated reels. Free content can be approved directly to the PWA feed. Premium content requires a credit price.</p>
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
                [
                    'label' => 'Premium',
                    'value' => function ($model) {
                        return (int) $model->is_paid === 1 ? 'Yes' : 'No';
                    },
                ],
                'created_at:datetime',
                [
                    'format' => 'raw',
                    'label' => 'Preview',
                    'value' => function ($model) {
                        return Html::a('Preview', ['preview', 'id' => $model->id], [
                            'class' => 'btn btn-default btn-sm',
                            'target' => '_blank',
                            'rel' => 'noopener',
                        ]);
                    },
                ],
                [
                    'format' => 'raw',
                    'label' => 'Evaluate',
                    'value' => function ($model) {
                        $isPaid = (int) $model->is_paid === 1;
                        $creditField = $isPaid
                            ? Html::input('number', 'price_credits', '', [
                                'class' => 'form-control',
                                'placeholder' => 'Credits',
                                'min' => 1,
                                'required' => true,
                                'style' => 'width:100px;margin-right:8px;',
                            ])
                            : Html::tag('span', 'Free reel', ['class' => 'text-muted', 'style' => 'margin-right:8px;display:inline-block;min-width:100px;']);

                        return Html::beginForm(['evaluate', 'id' => $model->id], 'post', ['class' => 'form-inline dl-appraisal-form'])
                            . $creditField
                            . Html::submitButton($isPaid ? 'Approve premium' : 'Approve to feed', [
                                'class' => 'btn btn-success btn-sm',
                                'name' => 'status',
                                'value' => 'active',
                            ])
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
