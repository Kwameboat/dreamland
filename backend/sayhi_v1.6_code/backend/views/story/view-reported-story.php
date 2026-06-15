<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
//use yii\grid\GridView;
use kartik\grid\GridView;
use yii\data\ArrayDataProvider;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'Reported Story Detail';

//$this->title = 'Ad Detail';
$this->params['breadcrumbs'][] = ['label' => 'post', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

?>


<div class="row">
    <div class="col-xs-12">
        <div class="box">

            <div class="box-body">



                <p>
                    
                    
                    
                    
                    <?php

                    echo Html::a('Reject Reported Request', ['reported-story-action', 'id' => $model->id,'type'=>'cancel'], ['class' => 'btn btn-primary','data' => [
                        'method' => 'post',
                    ]]);
                    echo '&nbsp;';
                    echo Html::a('Block Story', ['reported-story-action', 'id' => $model->id,'type'=>'block'], [
                        'class' => 'btn btn-danger',
                        'data' => [
                        'confirm' => 'Are you sure you want to block this story?',
                        'method' => 'post',
                      ],
                    ]);
                    echo '&nbsp;';
                    ?>
                </p>


                <div class="col-xs-6" style="padding:0px;">

                    <?=DetailView::widget([
                    'model' => $model,
                    'attributes' => [
                        
                        [
                            'attribute' => 'status',
                            'value' => function ($model) {
                                return $model->getStatus();
                            },

                        ],
                        
                        [
                            'attribute' => 'User',
                            'value' => function ($model) {

                                return Html::a($model->user->name, ['/user/view', 'id' => $model->user_id]);
                            },
                            'format' => 'raw',
                        ],
                        [
                            'attribute'  => 'type',
                            'label' => 'Type',
                            'value' => function ($model) {
                                return $model->getType();
                            }
                        ],
                        'description',
                        'background_color',
                       
                        [
                            'attribute'=>'image',
                            'value'=> function ($model) {
                                
                                 return Html::img($model->imageUrl, ['alt' => 'No Image', 'width' => '50px', 'height' => '50px']);
                                
                            },
                            'format' => 'raw',
                         ],
                         [
                            'attribute' => 'video',
                            'format' => 'raw',    
                            'value' => function ($data) {
                                
                                
                                    return '<video width="100" height="100" controls>
                                        <source src="' .$data->VideoUrl.'" type="video/mp4">
                                    </video>';
                        
                            },
                        ],
                        'created_at:datetime',
                        

                    ],
                ])?>
                </div>
                <div class="col-xs-6">

                
                 <?php 
                   
                    echo  GridView::widget([
                        'dataProvider' => new ArrayDataProvider([
                            'allModels' => $model->reportedStory,
                        ]),
                    // 'filterModel' => $searchModel,
                        'columns' => [
                            //['class' => 'yii\grid\SerialColumn'],
                            ['class' => 'kartik\grid\SerialColumn'],
                            [
                                'attribute' => 'user_id',
                                'value' => function ($model) {
                                    
                                    
                                    return Html::a(@$model->user['name'], ['/user/view', 'id' => @$model->user['id']]);
                                },
                                'format'=>'raw'
                            ],
                            'created_at:datetime',
                            [
                            'attribute' => 'status',
                            'value' => function ($model) {
                                return $model->getStatus();
                            },
                            'format'=>'raw'
                            ],
                            'resolved_at:datetime',
                            
                            
                        
                        ],
                        'tableOptions' => [
                            'id' => 'theDatatable',
                            'class' => 'table table-striped table-bordered table-hover',
                        ],
                        'toolbar' => [
                        ],

                        'pjax' => false,
                        'bordered' => false,
                        'striped' => false,
                        'condensed' => false,
                        'responsive' => true,
                        'hover' => true,
                        'floatHeader' => false,
                        //'floatHeaderOptions' => ['top' => $scrollingTop],
                        'showPageSummary' => false,
                        'summary'=> false,
                        
                        'panel' => [
                           // 'type' => GridView::TYPE_PRIMARY,
                            'heading'=>'Story Reported By',
                            
                        ],
                                    
                    ]); 
                    ?>

                  

                </div>
                
                <div class="box-header col-xs-12">
                  
                    <?php
                    
            //    echo  GridView::widget([
            //     'dataProvider' => new ArrayDataProvider([
                    
            //         'allModels' => $model->postComment,
            //     ]),
            // // 'filterModel' => $searchModel,
            //     'columns' => [
            //         //['class' => 'yii\grid\SerialColumn'],
            //         ['class' => 'kartik\grid\SerialColumn'],
            //         [
            //             'attribute' => 'user_id',
            //             'headerOptions' => ['style' => 'width:15%'],
            //             'value' => function ($model) {
                            
            //                 return Html::a($model->user['name'], ['/user/view', 'id' => $model->user['id']]);
            //              },
            //             'format'=>'raw'
            //         ],
            //         [
            //              'attribute' => 'created_at',
            //              'headerOptions' => ['style' => 'width:15%'],
            //               'value' => 'created_at',
            //               'format'=>'datetime'
                     
            //          ],
                 
            //         'comment',
                    
                    
                
            //     ],
            //     'tableOptions' => [
            //         'id' => 'theDatatable',
            //         'class' => 'table table-striped table-bordered table-hover',
            //     ],
            //     'toolbar' => [
            //     ],

                
            //     'panel' => [
            //     //    'type' => GridView::TYPE_PRIMARY,
            //         'heading'=>'Comments',
                    
            //     ],
                            
            // ]); 
               
               
               
               ?>
            

                </div>

               

            </div>

        </div>

    </div>