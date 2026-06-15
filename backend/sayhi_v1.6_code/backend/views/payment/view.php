<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\data\ArrayDataProvider;
use kartik\grid\GridView;
use yii\helpers\Url;
use common\models\Payment;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */
// . $model->title
$this->title = 'Payment Details ';
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
      <?php  if($model->status == Payment::STATUS_COMPLETED){ ?>
         <?= Html::a('Refund', ['refund', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>   
    <?php  }  ?>
       
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    [
                        'attribute'  => 'user_id',
                        'value' => function($model){
                            return $model->user->name;
                        }
                          
                    ],
                    [
                        'attribute'  => 'amount',
                        'value'  => function ($model) {
                            return '$'.$model->amount;
                        },
                    ],
                    'transaction_id',
                    [
                        'attribute'  => 'package_id',
                        'value' => function($model){
                            return $model->packageName->name;
                        }
                          
                    ],
                    [
                        'attribute'  => 'payment_type',
                        'value' => function($model){
                            return $model->paymentTypeString;
                        }
                          
                    ],
                    [
                        'attribute'  => 'payment_mode',
                        'value' => function($model){
                            return $model->paymentModeString;
                        }
                          
                    ],
                    [
                        'attribute'  => 'status',
                        'value' => function($model){
                            return $model->statusString;
                        },
                        'format'=>'raw'
                          
                    ],
                    
                   // 'website',
                  //  'last_active:datetime',
                    'created_at:datetime',
                    // 'updated_at:datetime'
                ],
            ])
        //   if($model->status == $model::STATUS_ACTIVE){

        //     $currentTime= time();
        //     if($model->end_date < $currentTime){

        //        // echo Html::a('Declare Result', ['declare-result', 'id' => $model->id], ['class' => 'btn btn-success']);

        //         echo Html::a('Declare Result', ['declare-img-result', 'id' => $model->id], ['class' => 'btn btn-success']);
        //     }

        // }
        
            
         ?>


    </p>




</div>


</div>

</div>
</div>
