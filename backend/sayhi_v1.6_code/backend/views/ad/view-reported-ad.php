<?php
use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\grid\GridView;
use yii\data\ArrayDataProvider;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'Ad Detail';
$this->params['breadcrumbs'][] = ['label' => 'Reported Ad', 'url' => ['reported-ads']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

?>


<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <!-- <div class="box-header">
                <h3 class="box-title"><?=Html::encode($this->title)?></h3>

            </div>
             -->
            <div class="box-body">



                <p>
                    <?=Html::a('Reject Reported Request', ['reported-ad-action', 'id' => $model->id,'type'=>'cancel'], ['class' => 'btn btn-primary','data' => [
                            'method' => 'post',
                        ]])?>

                    <?=Html::a('Block Ads', ['reported-ad-action', 'id' => $model->id,'type'=>'block'], [
    'class' => 'btn btn-danger',
    'data' => [
        'confirm' => 'Are you sure you want to block this item ad?',
        'method' => 'post',
    ],
])?>
                                            
                <?php
                
                $groupId = ($model->messageGroup) ? $model->messageGroup->id:0;


                echo Html::a('Chat', ['message/', 'group_id' => $groupId,'ad_id'=>$model->id], ['class' => 'btn btn-primary']);
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
                                return $model->statusString;
                            },

                        ],
                        'view',
                        'created_at:datetime',
                        'expire_date:datetime',
                        [
                            'attribute' => 'username',
                            'value' => function ($model) {

                                return Html::a($model->user->username, ['/user/view', 'id' => $model->id]);
                            },
                            'format' => 'raw',
                        ],
                        'price',
                        [
                            'attribute' => 'category',
                            'value' => function ($model) {
                                return @$model->mainCategory->name;
                            },
                        ],
                        [
                            'attribute' => 'sub_category',
                            'value' => function ($model) {
                                return @$model->subCategory->name;
                            },
                        ],
                       
                        'phone',
                        [
                            'attribute' => 'hide_phone',
                            'value' => function ($model) {
                                return $model->hidePhoneString;
                            },
                        ],
                        [
                            'attribute' => 'negotiable',
                            'value' => function ($model) {
                                return $model->negotiableString;
                            },
                        ],
                        [
                            'attribute' => 'featured',
                            'value' => function ($model) {
                                return $model->featuredString;
                            },
                        ],

                    ],
                ])?>
                </div>
                <div class="col-xs-6" >

            <?php 
                
                
                echo  GridView::widget([
                   'dataProvider' => new ArrayDataProvider([
                       'allModels' => $model->reportedAd,
                   ]),
                  // 'filterModel' => $searchModel,
                   'columns' => [
                       //['class' => 'yii\grid\SerialColumn'],
                       ['class' => 'kartik\grid\SerialColumn'],
                       [
                           'attribute' => 'user_id',
                           'value' => function ($model) {
                               return Html::a($model->user['username'], ['/user/view', 'id' => $model->user['id']]);
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
                    //    'type' => GridView::TYPE_PRIMARY,
                       'heading'=>'Ad Reported By',
                       
                   ],
                                  
               ]); ?>
              

       
                
                
                
                <p>



                <div id="myCarousel" class="carousel slide" data-ride="carousel">
                    <!-- Indicators -->

                    <!-- Wrapper for slides -->
                    <ol class="carousel-indicators">
                        <?php
                        $i = 0;
                        foreach ($model->adImage as $record) {
                            $classAdd = '';
                            if ($i == 0) {
                                $classAdd = 'active';
                            }
                            ?>
                        <li data-target="#myCarousel" data-slide-to="<?=$i?>" class="<?=$classAdd?>"></li>

                        <?php
                        $i++;
                        }?>
                        </ol>
                        <div class="carousel-inner">

                            <?php
                        $i = 0;
                        
                        foreach ($model->adImage as $record) {
                            $classAdd = '';
                            if ($i == 0) {
                                $classAdd = 'active';
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
