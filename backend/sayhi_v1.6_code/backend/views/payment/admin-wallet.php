<?php

//use yii\grid\GridView;
use kartik\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Admin Wallet History';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12"><div class="box">
            <!-- /.box-header -->
            
            
            <div class="box-body">
            <?php 
            
            $class = ($availableCoin>0)?'green':'red';
            ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'caption'=>"<h3 >Total Available Coins :<span style='color:".$class."'> $availableCoin </span></h3>",
                    'captionOptions' => [
                           'class' => 'text-white',
                    ],
                    'columns' => [
                        ['class' => 'kartik\grid\SerialColumn'],
                        
                       [
                            'attribute'  => 'coin',
                            'value'  => function ($model) {
                                return $model->coin;
                            },
                        ],
                        [
                            'attribute'  => 'Transaction',
                            'value' => function($model){
                                return $model->getTransactionTypeString();
                            },
                            'format'=>'raw'
                              
                        ], 
                       
                        [
                            'attribute'  => 'payment_type',
                            'value' => function($model){
                                return $model->getTypeString();
                            },
                            'format'=>'raw'
                              
                        ],
                        'created_at:datetime',
                     
                        
                      
                    ],
                    'tableOptions' => [
                        'id' => 'theDatatable',
                        'class' => 'table table-striped table-bordered table-hover',
                    ],
                     'toolbar' => [
                    
                        '{export}',
                        //'{toggleData}'
                    ],
                    //'toggleDataContainer' => ['class' => 'btn-group-sm'],
                    //'exportContainer' => ['class' => 'btn-group-sm'],
                    'exportConfig' => [
                        GridView::CSV => ['label' => 'CSV'],
                        GridView::EXCEL => [],// html settings],
                       
                    ],
            
                    'pjax' => false,
                    'bordered' => true,
                    'striped' => false,
                    'condensed' => false,
                    'responsive' => true,
                    'hover' => true,
                    'floatHeader' => false,
                    //'floatHeaderOptions' => ['top' => $scrollingTop],
                    'showPageSummary' => false,
                    'panel' => [
                        // 'type' => GridView::TYPE_PRIMARY
                    ],
                   
                ]); ?> 
            </div>
        </div>
        <!-- /.box -->

        <!-- /.col -->
    </div>
</div>