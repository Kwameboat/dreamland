<?php
use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $adType['title'];
$type = $adType['type'];


//$this->title = 'Ad Detail';
$this->params['breadcrumbs'][] = ['label' => 'Ad', 'url' => ['index']];
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



                <p>
                    <?= Html::a('Update', ['update', 'id' => $model->id,'type'=>$type], ['class' => 'btn btn-primary']) ?>
                    &nbsp;
                    <?= Html::a('Delete', ['delete', 'id' => $model->id,'type'=>$type], [
                        'class' => 'btn btn-danger',
                        'data' => [
                            'confirm' => 'Are you sure you want to delete this item?',
                            'method' => 'post',
                        ],
                    ]) ?>
                    <?php 
                    echo '&nbsp;';
                    
                     if($model->status==$model::STATUS_PENDING){
                     echo Html::a('Approve', ['approve', 'id' => $model->id,'type'=>$type], ['class' => 'btn btn-success']);
                     echo '&nbsp;';
                     
                     echo Html::a('Reject', ['reject', 'id' => $model->id,'type'=>$type], ['class' => 'btn btn-danger']);
                     }
                    //  echo '&nbsp;';

                    //  $groupId = ($model->messageGroup) ? $model->messageGroup->id:0;


                    //  echo Html::a('Chat', ['message/', 'group_id' => $groupId,'ad_id'=>$model->id], ['class' => 'btn btn-primary']);
                     
                     
                     if($model->featured==$model::FEATURED_NO){
                        echo '&nbsp;';
                        echo Html::a('Make this Ad Featured', ['make-featured', 'id' => $model->id,'type'=>$type], ['class' => 'btn btn-success']);
                        
                        
                        
                    }


                     
                     ?>
                </p>

                <div class="col-xs-6" style="padding:0px;">

                <?= DetailView::widget([
                    'model' => $model,
                    'attributes' => [
                        'title',
                        [
                            'attribute'  => 'status',
                            'value' => function($model){
                                return $model->statusString;
                            }
                            
                        ],
                        'view',
                        'created_at:datetime',
                        'expire_date:datetime',
                        [
                            'attribute'  => 'username',
                            'value' => function($model){
                                
                                return Html::a(@$model->user->username, ['/user/view', 'id' => $model->id]);
                            },
                            'format'=>'raw'
                        ],
                        'price',
                        [
                            'attribute'  => 'category',
                            'value' => function($model){
                                return @$model->mainCategory->name;
                            }
                        ],
                        [
                            'attribute'  => 'sub_category',
                            'value' => function($model){
                                return @$model->subCategory->name;
                            }
                        ],
                        /*[
                            'attribute'  => 'country',
                            'value' => function($model){
                                return $model->countryDetail->name;
                            }
                        ],
                        [
                            'attribute'  => 'state',
                            'value' => function($model){
                                return $model->stateDetail->name;
                            }
                        ],
                        [
                            'attribute'  => 'city',
                            'value' => function($model){
                                return $model->cityDetail->name;
                            }
                        ],*/
                        'phone',
                        [
                            'attribute'  => 'hide_phone',
                            'value' => function($model){
                                return $model->hidePhoneString;
                            }
                        ],
                        [
                            'attribute'  => 'negotiable',
                            'value' => function($model){
                                return $model->negotiableString;
                            }
                        ],
                        [
                            'attribute'  => 'package_banner_id',
                            'value'  => function ($model) {
                                return @$model->bannerPackage->name;
                            },
                        ],
                        [
                            'attribute'  => 'featured',
                            'value' => function($model){
                                return $model->featuredString;
                            }
                        ],
                        [
                            'attribute'  => 'is_banner_ad',
                            'value' => function($model){
                                return $model->isBannerAdString;
                            }
                        ],
                        [
                            'attribute'  => 'Is Deal Ad',
                            'value' => function($model){
                                return $model->isDealString;
                            }
                        ],
                        [
                            'attribute'  => 'Deal Date',
                            'visible'  =>  $model->isDeal,
                            'value' => function($model){
                                return $model->dealDate;
                               
                            }
                        ],
                        
                        
                        
                    
                    ],
                ]) ?>
                </div>
                <div class="col-xs-6" >
                <p>
               
                <div id="myCarousel" class="carousel slide" data-ride="carousel">
                    <!-- Indicators -->
                    
                    <!-- Wrapper for slides -->
                    <ol class="carousel-indicators">
                        <?php
                         $i=0;
                        foreach($model->adImage as $record){ 
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
                        
                        foreach($model->adImage as $record){
                            $classAdd ='';
                            if($i==0){
                                $classAdd ='active';
                            }
                            ?>
                            <div class="item <?=$classAdd?>">
                            <?= Html::img(Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_AD_IMAGES,$record->image), ['alt' => 'No Image' ]);?>
                            
                            </div>
                        <?php 
                        $i++;    
                        }
    
                        ?>


                    </div>

                    <!-- Left and right controls -->
                    <a class="left carousel-control" href="#myCarousel" data-slide="prev">
                        <span class="glyphicon glyphicon-chevron-left"></span>
                        <span class="sr-only">Previous</span>
                    </a>
                    <a class="right carousel-control" href="#myCarousel" data-slide="next">
                        <span class="glyphicon glyphicon-chevron-right"></span>
                        <span class="sr-only">Next</span>
                    </a>
                    </div>
                   <p>
                   <h3>Description</h3>
                   <?=$model->description?>
                
                   </p> 
                
                
                </div>

            </div>


        </div>

    </div>
    
</div>
