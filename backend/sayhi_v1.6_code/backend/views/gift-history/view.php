<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'User live history Details';
$this->params['breadcrumbs'][] = ['label' => 'User', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>


<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <!-- <div class="box-header">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>

            </div>
             -->
            <div class="box-body">

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            [
                'attribute'  => 'reciever_id',
                'label' => 'Gift Reciever',
                'value' => function ($model) {
                    return Html::a($model->user->name , ['/user/view', 'id' => @$model->user->id]);
                },
                // 'filter'=>Html::activeDropDownList($searchModel, 'reciever_id', $userData,['class'=>'form-control','prompt' => 'All']),
                'format'=>'raw'
               ],
               [
                'attribute'  => 'sender_id',
                'label' => 'Gift Sender',
                'value' => function ($model) {
                    return Html::a($model->senderUser->name , ['/user/view', 'id' => @$model->user->id]);
                },
                'format'=>'raw'
            ],
            'coin',
            'coin_actual',
            'created_at:datetime',
        ],
    ]) ?>

</div>


</div>

</div>
</div>
