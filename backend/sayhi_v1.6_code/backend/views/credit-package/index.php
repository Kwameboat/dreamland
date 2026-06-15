<?php

use yii\grid\GridView;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Credit Package Manager';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="credit-package-index box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Admin Credit Packages (GHS Mobile Money)</h3>
        <?= Html::a('Add Package', ['create'], ['class' => 'btn btn-success pull-right']) ?>
    </div>
    <div class="box-body table-responsive">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => [
                'credit_amount',
                'fiat_cost:decimal',
                'currency',
                [
                    'attribute' => 'is_active',
                    'value' => function ($model) {
                        return $model->is_active ? 'Active' : 'Inactive';
                    },
                ],
                'created_at',
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{update} {delete}',
                ],
            ],
        ]) ?>
    </div>
</div>
