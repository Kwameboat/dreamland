<?php

//use yii\grid\GridView;
use kartik\grid\GridView;
use yii\helpers\Html;
//print_r($adType);
$this->title = 'Reported Ads';


$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12">
    <div class="box">
            <!-- /.box-header -->
            <div class="box-body">
                
                

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        //['class' => 'yii\grid\SerialColumn'],
                        ['class' => 'kartik\grid\SerialColumn'],
                        'title',
                        [
                            'attribute'  => 'user_id',
                            'value' => function($model){
                                
                                return Html::a($model->user->username, ['/user/view', 'id' => $model->id]);
                            },
                            'format'=>'raw'
                        ],
                       
                        [
                            'attribute'  => 'status',
                            'value'  => function ($data) {
                                return $data->statusString;
                            },
                        ],
                        'created_at:datetime',
                        
                        [
							'class' => 'yii\grid\ActionColumn',
							 'header' => 'Action',
                             'template' => '{view}',
                                                        
                                'urlCreator' => function ($action, $model, $key, $index) {

                                    if ($action === 'view') {
                                      
                                        $url = 'index.php?r=ad/view-reported-ad&id='.$model['id'];

                                        return $url;

                                    }

                                   
                                    if($action === 'delete') {

                                        $url = 'index.php?r=ad/delete&id='.$model['id'];

                                        return $url;

                                    }

                                },

                             'buttons'=> [
                                'delete'=>function($url,$model,$key) {
                                        //return Html::a( '<span class="glyphicon glyphicon-trash"></span>', $url);
                                        return Html::a('<span class="glyphicon glyphicon-trash"></span>',  $url, [
                                            //'class' => 'btn btn-danger',
                                            'data' => [
                                                'confirm' => 'Are you sure you want to delete this item?',
                                                'method' => 'post',
                                            ],
                                        ]);
                                
                                    
                                 
                
                                },
                            ], 
                         ],
                    
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
                    'exportContainer' => ['class' => 'btn-group-sm'],
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