<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\data\ArrayDataProvider;
use kartik\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'Payment Request Detail:';
$this->params['breadcrumbs'][] = ['label' => 'Package', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
//\yii\web\YiiAsset::register($this);



?>


<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <!-- <div class="box-header">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>

            </div>
             -->
            <div class="box-body">



    <p>
    <?php if($model->status== $model::STATUS_PENDING){ ?>
        <?= Html::a('Complete', ['update', 'id' => $model->id,'status'=>10], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Reject', ['update', 'id' => $model->id,'status'=>2], ['class' => 'btn btn-danger']) ?>
        <?php }?>
        
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            [
                'attribute'  => 'status',
                'value' => function($model){
                    return $model->statusButton;
                },
                'format'=>'raw'
                
            ],
            
            [
                'attribute'  => 'amount',
                'value'  => function ($model) {
                    return '$'.$model->amount;
                },
            ],
            [
                'attribute'  => 'user_id',
                'value' => function($model){
                    
                    return Html::a($model->user->name, ['/user/view', 'id' => $model->user_id]);
                },
                'format'=>'raw'
            ],
            [
                'attribute'  => 'User Paypal Id',
                'value' => function($model){
                    
                    return $model->user->paypal_id;
                },
                'format'=>'raw'
            ],

            'transaction_id',
            'description',            
            'created_at:datetime',
            'updated_at:datetime',
          
        ],
    ]) ?>



       
             

</div>


</div>

</div>
</div>
