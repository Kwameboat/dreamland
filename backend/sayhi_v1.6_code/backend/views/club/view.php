<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\data\ArrayDataProvider;
use kartik\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'Detail : '. $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Package', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
//\yii\web\YiiAsset::register($this);
?>


<div class="row">
    <div class="col-xs-12">
        <div class="box">
          
            <div class="box-body">



    <p>
     
        
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>

     

    </p>

    <?= DetailView::widget([
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
            [
                'label'  => 'Category',
                'value'  => function ($data) {
                    return @$data->clubCategory->name;
                },
                'format'=>'raw'
            ],
            [
                'label'  => 'Privacy',
                'value'  => function ($data) {
                    return $data->privacyTypeString;
                },
                'format'=>'raw'
            ],
            [
                'label'  => 'Is Chat Group',
                'value'  => function ($data) {
                    return $data->isChatRoomString;
                },
                'format'=>'raw'
            ],

            

            [
                'label'  => 'Club Post',
                'value'  => function ($data) {
                    return count($data->post);
                },
                'format'=>'raw'
            ],
            
            [
                'label'  => 'Joined Users',
                'value'  => function ($data) {
                    return count($data->clubUser);
                },
                'format'=>'raw'
            ],
            [
                'label'  => 'Created By',
                'value'  => function ($data) {
                    return Html::a($data->user->username, ['/user/view', 'id' => $data->user->id]);
                    
                },
                'format'=>'raw'
            ],
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
                'attribute'=>'description',
                'value'=> function ($model) {
                    return nl2br($model->description);
                },
                'format' => 'raw',
            ],

            
        ],
    ]) ?>



        <?php 
        
        echo '<br>';
             
                
        echo  GridView::widget([
           'dataProvider' => new ArrayDataProvider([
               'allModels' => $model->clubUser,
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
              

               [
                'attribute'  => 'Username',
                    'value'  => function ($data) {
                        
                        return Html::a($data->userDetail->username, ['/user/view', 'id' => $data->userDetail->id]);
                    },
                    'format' => 'raw',
                ],
                [
                    'attribute'  => 'email',
                        'value'  => function ($data) {
                            
                            return $data->userDetail->email;
                        },
                        'format' => 'raw',
                 ],
                 
                'created_at:datetime',
             
           
           ],
           'tableOptions' => [
               'id' => 'theDatatable',
               'class' => 'table table-striped table-bordered table-hover',
           ],
           'toolbar' => [
           ],

           'pjax' => true,
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
               'heading'=>'Club users',
               'footer'=>'Total users : '.count($model->clubUser)
               
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
                      'heading'=>'Club posts',
                      //'footer'=>'Totoal Price : '.$model->slotBookingAmount
                      
                  ],
                                 
              ]); 
              
             
              ?>
             

</div>


</div>

</div>
</div>
