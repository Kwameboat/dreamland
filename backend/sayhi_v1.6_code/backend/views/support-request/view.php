<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\data\ArrayDataProvider;
use kartik\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'Support Request';
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
        
        <?php 
          if($model->is_reply == $model::COMMON_NO){

            
                echo Html::a('Reply', ['reply', 'id' => $model->id], ['class' => 'btn btn-primary']);
            

            }
        
            
         ?>


    </p>

    <?= DetailView::widget([
        'options' => ['class' => 'table table-striped table-bordered detail-view fix-width'],
        'model' => $model,
        'attributes' => [
            [
                'attribute'  => 'is_reply',
                'value' => function($model){
                    return $model->isReplyButton;
                },
                'format'=>'raw'
                
            ],
            [
                'label'  => 'User',
                'value'  => function ($model) {
                    return Html::a(@$model->user->username, ['/user/view', 'id' => $model->user_id]);
                },
                'format'=>'raw'
            ],
            'name',
            'email',
            'phone',
           
            'created_at:datetime',
            'request_message',
            
            'updated_at:datetime',
            'reply_message'
            
            
        ],
    ]) ?>



             

</div>


</div>

</div>
</div>
<style>
    .fix-width > tbody > tr > th {
    width: 20%;
}
    </style>