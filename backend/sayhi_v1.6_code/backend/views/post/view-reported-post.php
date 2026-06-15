<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
//use yii\grid\GridView;
use kartik\grid\GridView;
use yii\data\ArrayDataProvider;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'Reported Post Detail';

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

                    echo Html::a('Reject Reported Request', ['reported-post-action', 'id' => $model->id,'type'=>'cancel'], ['class' => 'btn btn-primary','data' => [
                        'method' => 'post',
                    ]]);
                    echo '&nbsp;';
                    echo Html::a('Block Post', ['reported-post-action', 'id' => $model->id,'type'=>'block'], [
                        'class' => 'btn btn-danger',
                        'data' => [
                        'confirm' => 'Are you sure you want to block this post?',
                        'method' => 'post',
                      ],
                    ]);
                    echo '&nbsp;';


                    if ($model->type == $model::TYPE_COMPETITION) {
                        

                        echo Html::a('View Competition', ['competition/view', 'id' => $model->competition->id], ['class' => 'btn btn-primary']);
                        echo '&nbsp;';
                       
                        if($model->competition->status == $model->competition::STATUS_ACTIVE){

                            $currentTime= time();
                            if($model->competition->end_date < $currentTime){

                                echo  Html::a('Make this image Winner', ['competition/winning', 'id' => $model->id], [
                                    'class' => 'btn btn-success',
                                    'data' => [
                                        'confirm' => 'Are you sure you want award winning this image?',
                                        'method' => 'post',
                                    ],
                                ]); 
                            }
                            //echo Html::a('Make Winning Competition', ['update-status', 'id' => $model->id, 'type' => 'block'], ['class' => 'btn btn-success']);
                        }
                        echo '&nbsp;';
                        if($model->competition->winner_id == $model->id){
                            echo '<span class="label label-success">Competition Winner Image</span>';
                        }


                    } 

                    ?>
                </p>


                <div class="col-xs-6" style="padding:0px;">

                    <?=DetailView::widget([
                    'model' => $model,
                    'attributes' => [
                        'title',
                        [
                            'attribute' => 'status',
                            'value' => function ($model) {
                                return $model->getStatus();
                            },

                        ],
                        'total_view',
                        'total_like',
                        'popular_point',
                        'total_comment',
                        [
                            'attribute' => 'User',
                            'value' => function ($model) {

                                return Html::a($model->user->name, ['/user/view', 'id' => $model->user_id]);
                            },
                            'format' => 'raw',
                        ],
                        'created_at:datetime',
                        [
                            'attribute' => 'Image',
                            'value' => function ($model) {
                                $str='';
                                foreach($model->postGallary as $record){
                                    
                                    if($record->media_type ==1){    
                                        $str = $str.Html::img($record->filenameUrl, ['alt' => 'No Image', 'width'=>'100%' ]);
                                    }elseif($record->media_type ==2){
                                        
                                        $str = $str.'<video  width="100%" poster="'.$record->videoThumbUrl.'"   controls >
                                        <source src="'.$record->filenameUrl.'" type="video/mp4">
                                        <source src="movie.ogg" type="video/ogg"></video>';
                                    
                                    }
                                    $str = $str.'<br><br>';

                                }
                                return $str;
                    
                                //return  Html::img($model->imageUrl, ['alt' => 'No Image' ]);

                            },
                            'format' => 'raw',
                        ],

                    ],
                ])?>
                </div>
                <div class="col-xs-6">

                
                 <?php 
                   
                    echo  GridView::widget([
                        'dataProvider' => new ArrayDataProvider([
                            'allModels' => $model->reportedPost,
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
                            'heading'=>'Post Reported By',
                            
                        ],
                                    
                    ]); 
                    ?>

                  

                </div>
                
                <div class="box-header col-xs-12">
                  
                    <?php
                    
               echo  GridView::widget([
                'dataProvider' => new ArrayDataProvider([
                    
                    'allModels' => $model->postComment,
                ]),
            // 'filterModel' => $searchModel,
                'columns' => [
                    //['class' => 'yii\grid\SerialColumn'],
                    ['class' => 'kartik\grid\SerialColumn'],
                    [
                        'attribute' => 'user_id',
                        'headerOptions' => ['style' => 'width:15%'],
                        'value' => function ($model) {
                            
                            return Html::a($model->user['name'], ['/user/view', 'id' => $model->user['id']]);
                         },
                        'format'=>'raw'
                    ],
                    [
                         'attribute' => 'created_at',
                         'headerOptions' => ['style' => 'width:15%'],
                          'value' => 'created_at',
                          'format'=>'datetime'
                     
                     ],
                 
                    'comment',
                    
                    
                
                ],
                'tableOptions' => [
                    'id' => 'theDatatable',
                    'class' => 'table table-striped table-bordered table-hover',
                ],
                'toolbar' => [
                ],

                
                'panel' => [
                //    'type' => GridView::TYPE_PRIMARY,
                    'heading'=>'Comments',
                    
                ],
                            
            ]); 
               
               
               
               ?>
            

                </div>

               

            </div>

        </div>

    </div>