<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'Post Detail';

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
                    if ($model->status == $model::STATUS_ACTIVE) {
                        echo Html::a('Block Post', ['update-status', 'id' => @$model->id, 'type' => 'block'], ['class' => 'btn btn-danger']);
                    } else if ($model->status == $model::STATUS_BLOCKED) {
                        echo Html::a('Reactive Post', ['update-status', 'id' => @$model->id, 'type' => 'active'], ['class' => 'btn btn-success']);
                        //echo '&nbsp;';

                    }
                    echo '&nbsp;';

                    if ($model->type == $model::TYPE_COMPETITION) {
                        

                        echo Html::a('View Competition', ['competition/view', 'id' => @$model->competition->id], ['class' => 'btn btn-primary']);
                        echo '&nbsp;';
                       /*
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
                            
                        }
                        echo '&nbsp;';*/
                        if(@$model->competition->winner_id == @$model->id){
                            echo '<span class="label label-success">Competition Winner Image</span>';
                        }


                    } 

                    echo '&nbsp;';
                    if (@$model->poll_id != null) {
                        echo Html::a('View Poll', ['poll/view', 'id' => @$model->poll_id], ['class' => 'btn btn-primary']);
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

                    ],
                ])?>
                </div>
                <div class="col-xs-6">

                    <?php /*
                    
                foreach($model->postGallary as $record){
                            
                            if($record->media_type ==1){
                                echo Html::img($record->filenameUrl, ['alt' => 'No Image' ]);

                            }elseif($record->media_type ==2){
                                //$videoUrl  = $record->filenameUrl;
                                $videoUrl = 'https://image-selling.s3.amazonaws.com/competition/16234335285971_20210611_174528_9494dc98e5.mp4';
                                echo '<video  width="100%" poster="'.$record->videoThumbUrl.'"   controls autoplay>
                            <source src="'.$videoUrl.'" type="video/mp4">
                            <source src="movie.ogg" type="video/ogg"></video>' ;
                            }
                            ?>
                            
                            
                        <?php 
                       
                        }*/
                        ?>

                        <?php  ?>

                <div id="myCarousel" class="carousel slide" data-ride="carousel">
                    <!-- Indicators -->
                    
                    <!-- Wrapper for slides -->
                    <ol class="carousel-indicators">
                        <?php
                         $i=0;
                        foreach($model->postGallary as $record){ 
                            $classAdd ='';
                            if($i==0){
                                $classAdd ='active';
                            }
                            ?>
                        <li data-target="#myCarousel" data-slide-to="<?=$i?>" class="<?=$classAdd?>"></li>
                        
                        <?php 
                        $i++;
                        } ?>
                    </ol>
                    <div class="carousel-inner">
                       
                        <?php 
                        $i=0;
                        foreach($model->postGallary as $record){
                            $classAdd ='';
                            if($i==0){
                                $classAdd ='active';
                            }
                            ?>
                            <div class="item <?=$classAdd?>">

                            <?php 
                            
                            if($record->media_type ==1){
                                echo Html::img($record->filenameUrl, ['alt' => 'No Image','width'=>"100%" ]);

                            }elseif($record->media_type ==2){
                                
                               //$videoUrl = 'https://image-selling.s3.amazonaws.com/competition/16234335285971_20210611_174528_9494dc98e5.mp4';
                                echo '<video  width="100%" poster="'.$record->videoThumbUrl.'"   controls >
                            <source src="'.$record->filenameUrl.'" type="video/mp4">
                            <source src="movie.ogg" type="video/ogg"></video>' ;
                            }
                            
                            ?>
                            
                            </div>

                        <?php 
                      
                      
                        $i++;    
                        }
    
                        ?>


                    </div>

                    
                   <a class="left carousel-control" href="#myCarousel" data-slide="prev">
                        <span class="glyphicon glyphicon-chevron-left"></span>
                        <span class="sr-only">Previous</span>
                    </a>
                    <a class="right carousel-control" href="#myCarousel" data-slide="next">
                        <span class="glyphicon glyphicon-chevron-right"></span>
                        <span class="sr-only">Next</span>
                    </a>
                    </div>

                    <?php  ?>
                   

                
                
                
                </div>
                
                <div class="box-header col-xs-12">
                    <h3 class="box-title">Comments</h3>

                </div>

                <?php 
                
                
                echo  GridView::widget([
                   'dataProvider' => new ArrayDataProvider([
                       'allModels' => $model->postComment,
                       'pagination' => [
                        'pageSize' => 40,
                        ]
                   ]),
                  // 'filterModel' => $searchModel,
                   'columns' => [
                       ['class' => 'yii\grid\SerialColumn',
                       'headerOptions' => ['style' => 'width:5%']
                    
                        ],
                       //['class' => 'kartik\grid\SerialColumn'],
                       [
                           'attribute' => 'user_id',
                           'headerOptions' => ['style' => 'width:15%'],
                           'value' => function ($model) {
                               return Html::a($model->user['username'], ['/user/view', 'id' => $model->user['id']]);
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
                   'summary'=> false,
                   
                   
                                  
               ]); ?>
            


            </div>

        </div>

    </div>