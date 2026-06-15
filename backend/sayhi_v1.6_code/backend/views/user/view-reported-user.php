<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\grid\GridView;
use yii\data\ArrayDataProvider;


$this->title = 'Reported User Detail : '. $model->name;
$this->params['breadcrumbs'][] = ['label' => 'User', 'url' => ['index']];
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
                <?php

                    echo Html::a('Reject Reported Request', ['reported-user-action', 'id' => $model->id,'type'=>'cancel'], ['class' => 'btn btn-primary','data' => [
                        'method' => 'post',
                    ]]);
                    echo '&nbsp;';
                    echo Html::a('Block User', ['reported-user-action', 'id' => $model->id,'type'=>'block'], [
                        'class' => 'btn btn-danger',
                        'data' => [
                        'confirm' => 'Are you sure you want to block this user?',
                        'method' => 'post',
                    ],
                    ]);
                    echo '&nbsp;';
                    ?>

                    <?= Html::a('View all post', ['post/index', 'PostSearch[user_id]' => $model->id], ['class' => 'btn btn-primary']) ?>

                </p>

                <div class="col-xs-6" style="padding:0px;">

                    <?= DetailView::widget([
                    'model' => $model,
                    'attributes' => [
                        'name',
                        'email',
                        
                        [
                            'attribute'  => 'country_id',
                            'value'  => function ($data) {
                                return @$data->country->name;
                            },
                        ],
                    // 'website',
                        
                    /* [
                            'attribute'  => 'sex',
                            'value'  => function ($data) {
                                return $data->getSex();
                            },
                        ],*/

                        /*'phone',
                        'address',
                        'postcode',
                        'country',
                        'city',*/
                        'bio',
                        'description',
                        'available_balance',
                        'available_coin',
                        'last_active:datetime',
                        'created_at:datetime',
                        'updated_at:datetime',
                        [
                            'attribute'=>'image',
                            'value'=> function ($model) {
                                
                                return Html::img($model->imageUrl, ['alt' => 'No Image', 'width' => '50px', 'height' => '50px']);
                                // return Html::img(Yii::$app->urlManagerFrontend->baseUrl.'/uploads/promotional-banner/thumb/'.$model->image, ['alt' => 'No Image', 'width' => '50px', 'height' => '50px']);
                            },
                            'format' => 'raw',
                        ]
                        
                    ],
                ]) ?>
                </div>
                <div class="col-xs-6">
                <?php 
                   
                   echo  GridView::widget([
                       'dataProvider' => new ArrayDataProvider([
                           'allModels' => $model->reportedUser,
                       ]),
                   // 'filterModel' => $searchModel,
                       'columns' => [
                           //['class' => 'yii\grid\SerialColumn'],
                           ['class' => 'kartik\grid\SerialColumn'],
                           [
                               'attribute' => 'user_id',
                               'value' => function ($model) {
                                   
                                   
                                   return Html::a($model->user['name'], ['/user/view', 'id' => $model->user['id']]);
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
                           'heading'=>'User Reported By',
                           
                       ],
                                   
                   ]); 
                   ?>

                </div>
            </div>


        </div>

    </div>
</div>