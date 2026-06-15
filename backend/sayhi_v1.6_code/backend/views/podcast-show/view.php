<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ActiveForm;
// use yii\grid\GridView;
use yii\data\ArrayDataProvider;
use kartik\grid\GridView;
use yii\helpers\Url;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'Podcast Show Details :'.$model->name;
$this->params['breadcrumbs'][] = $this->title;
?>


<div class="row">
    <div class="col-xs-12">
        <div class="box">
             <div class="box-header">
             <div class="pull-center"><?= Html::a('Create Episode', ['podcast-show-episode/create', 'show_id' => $model->id], ['class' => 'btn btn-success pull-right']) ?></div> 

            </div>
            
            <div class="box-body">

                <?= DetailView::widget([
                'model' => $model,
                'attributes' => [           
                    'name',
                    'language',
                    'age_group',                    
                    'show_time:datetime',
                    [
                        'attribute'  => 'category_id',
                        'value' => function($model){
                            return @$model->category->name;
                            
                            
                        },

                    ],
                    [
                        'attribute'  => 'podcast_channel_id',
                        'value' => function($model){
                            return @$model->poscastHostName->name;
                            
                            
                        },

                    ],
                    [
                        'attribute' => 'image',
                        'format' => 'html',    
                        'value' => function ($data) {
                            return Html::img($data->imageUrl, ['width' => '70px','height' => '60px']);
                        },
                    ],
                    'description',
                    [
                        'attribute'  => 'status',
                        'value' => function($model){
                            return $model->getStatus();
                        },
                        'format'=>'raw'
                        
                    ],
                    'created_at:datetime',        
                ],
            ]) ?>

       <?php   echo "<br>"; ?>
             
          <?php  echo  GridView::widget([
                  'dataProvider' => new ArrayDataProvider([
                      'allModels' => $model->podcastShowAllEpisode,
                      
                  ]),
                 // 'filterModel' => $searchModel,
                  'columns' => [
                      //['class' => 'yii\grid\SerialColumn'],
                     // ['class' => 'kartik\grid\SerialColumn'],
                        [
                            'label'  => 'ID',
                            'attribute'  => 'id',
                            'value' => function($model){
                                
                                 return ''.$model->id;
                                
                            
                            },
                            'format'=>'raw'
                        ],
                        [
                            'attribute'  => 'name',
                            'value' => function($model){
                                
                                return Html::a($model->name, ['/podcast-show-episode/view-detail', 'id' => $model->id]);
                            },
                            'format'=>'raw'
                        ],
                    [
                        'attribute' => 'image',
                        'format' => 'html',    
                        'value' => function ($data) {
                            return Html::img($data->imageUrl, ['width' => '70px','height' => '60px']);
                        },
                    ],
                    'episode_period',
                    [
                        'attribute'  => 'status',
                        'value'  => function ($data) {
                            return $data->getStatus();
                        },
                    ],  
                    [
                        'class' => 'yii\grid\ActionColumn',
                         'header' => 'Action',
                         'template' => '{viewdetail} {update} {delete}',
                         'urlCreator' => function ($action, $model, $key, $index) {

                            if($action === 'update') {
                                $url = Url::to(['podcast-show-episode/update', "id" => $model['id']]);
                                return $url;
            
                            }
                            if($action === 'delete') {
                                $url = Url::to(['podcast-show-episode/delete', "id" => $model['id']]);
                                return $url;
            
                            }
            
                            if($action === 'viewdetail') {
                                $url = Url::to(['podcast-show-episode/view-detail', "id" => $model['id']]);
                                return $url;
            
                            }

            
                        },
            
                        'buttons' => [
            
                            'viewdetail' => function ($url, $model, $key) {
            
                                return Html::a('<span class="fa fa-eye fa-lg"></span>', $url, ['title' => 'View Episode']);
            
                            },
                        ],
                     ],                
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
                      'type' => GridView::TYPE_PRIMARY,
                      'heading'=>'Podcast Show Episode',
                      
                  ],
                                 
              ]); ?>
</div>

</div>


</div>

</div>
</div>
