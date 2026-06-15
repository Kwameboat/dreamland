<?php

//use yii\grid\GridView;
use kartik\grid\GridView;
use yii\helpers\Html;
//print_r($adType);
$this->title = $adType['title'];
$type = $adType['type'];

$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12"><div class="box">
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
                            //'headerOptions' => ['style' => 'width:15%'],
                            'value' => function($model){
                                
                                return Html::a($model->user->name, ['/user/view', 'id' => $model->user_id]);
                            },
                            //filter'=>Html::activeDropDownList($searchModel, 'user_id', $userData,['class'=>'form-control select','prompt' => 'All']),

                            'filter' => $userData,
                            'filterType' => GridView::FILTER_SELECT2,
                            'filterWidgetOptions' => [
                                'options' => ['prompt' => 'select'],
                                'pluginOptions' => [
                                    'allowClear' => true,
                                    'width'=>'200'
                                  
                                ],
                            ],
                            
                            'format'=>'raw'
                        ],
                      


                       /* [
                            'attribute'  => 'city_id',
                            'value'  => function ($model) {
                                return $model->cityDetail->name;
                            },
                        ],*/
                        [
                            'attribute'  => 'package_banner_id',
                            'value'  => function ($model) {
                                return @$model->bannerPackage->name;
                            },
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
                             'template' => '{view} {update} {delete}',
                                                        
                                'urlCreator' => function ($action, $model, $key, $index) use ($type) {

                                    if ($action === 'view') {
                                      
                                        $url = 'index.php?r=ad/view&id='.$model['id'].'&type='.$type;

                                        return $url;

                                    }

                                    if($action === 'update') {

                                        $url = 'index.php?r=ad/update&id='.$model['id'].'&type='.$type;

                                        return $url;

                                    }

                                    if($action === 'delete') {

                                        $url = 'index.php?r=ad/delete&id='.$model['id'].'&type='.$type;

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
                                'update'=>function($url,$model,$key) use ($type) {
                                    
                                    if($type =='active')
                                    {
                                        
                                        return Html::a( '<span class="glyphicon glyphicon-pencil"></span>', $url);
                                    }
                                    
                
                                },
                            ], 
                         ],
                    
                    ],
                    'tableOptions' => [
                        'id' => 'theDatatable',
                        'class' => 'table table-striped table-bordered table-hover',
                    ],
                   
                    'toolbar' => [
                    
                        /*[
                            'content'=>
                                Html::button('<i class="glyphicon glyphicon-plus"></i>', [
                                    'type'=>'button', 
                                    'title'=>Yii::t('app', 'Add Book'), 
                                    'class'=>'btn btn-success'
                                ]) . ''.
                                Html::a('<i class="fas fa-redo">a</i>', ['grid-demo'], [
                                    'class' => 'btn btn-secondary', 
                                    'title' => Yii::t('app', 'Reset Grid')
                                ]),
                            //'options' => ['class' => 'btn-group-sm']
                        ],*/
                        '{export}',
                        //'{toggleData}'
                    ],
                    //'toggleDataContainer' => ['class' => 'btn-group-sm'],
                    'exportContainer' => ['class' => 'btn-group-sm'],
                    'exportConfig' => [
                        GridView::CSV => ['label' => 'CSV'],
                        GridView::EXCEL => [],// html settings],
                       
                    ],
            
                    
                    
                    /*
                    'toolbar' =>  [
                        ['content'=>
                            Html::button('&lt;i class="glyphicon glyphicon-plus">&lt;/i>', ['type'=>'button', 'title'=>Yii::t('app', 'Add Book'), 'class'=>'btn btn-success', 'onclick'=>'alert("This will launch the book creation form.\n\nDisabled for this demo!");']) . ' '.
                            Html::a('&lt;i class="glyphicon glyphicon-repeat">&lt;/i>', ['grid-demo'], ['data-pjax'=>0, 'class' => 'btn btn-default', 'title'=>Yii::t('app', 'Reset Grid')])
                        ],
                    ],*/   
                    
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