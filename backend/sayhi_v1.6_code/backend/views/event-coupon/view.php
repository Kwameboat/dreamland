<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\data\ArrayDataProvider;
use kartik\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'Event Detail : '. $model->name;
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
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>

       


    </p>

    <?php 
    
    
    echo DetailView::widget([
        'model' => $model,
        'attributes' => [
            [
                'attribute'  => 'status',
                'value' => function($model){
                    return $model->statusButton;
                },
                'format'=>'raw'
                
            ],
           
            'name',
            'start_date:datetime',
            'end_date:datetime',
            
            'created_at:datetime',
            'updated_at:datetime',
            
            [
                'attribute'=>'image',
                'value'=> function ($model) {
                     return Html::img($model->imageUrl, ['alt' => 'No Image', 'width' => '50px', 'height' => '50px']);
                },
                'format' => 'raw',
            ],
             [
                'attribute'=>'gallaryFile',
                'value'=> function ($model) {
                    $imagesString='';
                    foreach($model->gallaryImages as $photo){

                       
                            //return $photo->imageUrl;
                            $imagesString = $imagesString.' '.Html::img($photo->imageUrl, ['alt' => 'No Image', 'width' => '60px', 'height' => '60px']);   
                        
                        
                    }
                    return $imagesString;
                   
                },
                'format' => 'raw',
            ],

           
            'description',            
        ],
    ]) ?>



        <?php 
        /*
        
        echo '<br>';
               
                
        echo  GridView::widget([
           'dataProvider' => new ArrayDataProvider([
               'allModels' => $model->competitionPosition,
               'sort' => ['attributes' => [
                   
                     'id' => [
                         'asc' => ['id' => SORT_ASC],
                         'desc' => ['id' => SORT_DESC],
                         'default' => SORT_ASC
                     ],  
             
                 ],
                 'defaultOrder' => [
                     'id' => SORT_ASC
                 ]
             ]

               
           ]),
           'rowOptions' => function ($model) {
            
             },
          // 'filterModel' => $searchModel,
           'columns' => [
               //['class' => 'yii\grid\SerialColumn'],
              // ['class' => 'kartik\grid\SerialColumn'],
               
                'title',
                [
                    'label'  => "Award Value ($model->awardTypeString)",
                    'value'  => function ($data) {
                        return $data->award_value;
                    },
                    'format'=>'raw'
                ],
               
                
                [
                    'label'  => 'Winner Post',
                    'attribute'  => 'winner_post_id',
                    'value' => function($model){
                        if($model->winner_post_id){
                             return Html::a($model->post->title, ['/post/view', 'id' => $model->winner_post_id]);
                        }else{
                            return 'Not awarded';
                        }
                       
                    },
                    'format'=>'raw'
                ],
              
              
               'awarded_at:datetime',
               
           
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
               'heading'=>'Competition Position',
               //'footer'=>'Totoal Price : '.$model->slotBookingAmount
               
           ],
                          
       ]); 
      
       
        
        
        
        echo '<br>';
               
                
               echo  GridView::widget([
                  'dataProvider' => new ArrayDataProvider([
                      'allModels' => $model->post,
                      'sort' => ['attributes' => [
                            'total_view',
                            'total_like',
                            'total_comment',
                            'popular_point' => [
                                'asc' => ['popular_point' => SORT_ASC],
                                'desc' => ['popular_point' => SORT_DESC],
                                'default' => SORT_DESC
                            ],  
                    
                        ],
                        'defaultOrder' => [
                            'popular_point' => SORT_DESC
                        ]
                    ]

                      
                  ]),
                  'rowOptions' => function ($model) {
                        if ($model->is_winning == 1) {
                            return ['class' => 'success'];
                        }
                    },
                 // 'filterModel' => $searchModel,
                  'columns' => [
                      //['class' => 'yii\grid\SerialColumn'],
                     // ['class' => 'kartik\grid\SerialColumn'],
                        [
                            'label'  => '#ID',
                            'attribute'  => 'id',
                            'value' => function($model){
                                
                                 return '#'.$model->id;
                                
                            
                            },
                            'format'=>'raw'
                        ],
                       
                      
                       'title',
                       [
                           'attribute'  => 'user_id',
                           'value' => function($model){
                               
                               return Html::a($model->user->name, ['/user/view', 'id' => $model->user_id]);
                           },
                           'format'=>'raw'
                       ],
                     
                       'total_view',
                       'total_like',
                       'total_comment',
                       'popular_point',
                       
                       [
                           'attribute'  => 'status',
                           'value'  => function ($data) {
                               return $data->getStatus();
                           },
                       ],
                     
                      'created_at:datetime',
                        [
                           'class' => 'yii\grid\ActionColumn',
                            'header' => 'Action',
                            'template' => '{view}',
                            'urlCreator' => function ($action, $model, $key, $index) {
        
                                if ($action === 'view') {
                                    $url =     Url::to(['post/view','id'=>$model['id']]);
                                    return $url;

                                }


                            },

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
                      'heading'=>'Competition User post',
                      //'footer'=>'Totoal Price : '.$model->slotBookingAmount
                      
                  ],
                                 
              ]); 
              */
             
              ?>
             

</div>


</div>

</div>
</div>
