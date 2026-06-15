<?php

use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Story';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12"><div class="box">
            <!-- /.box-header -->
            <div class="box-body">
                <div style="clear:both"></div>


                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        
                        // [
                        //     'attribute'=>'filter_id',
                        //     'value' => function ($model) {
                        //         return '';
                               
                        //     },
                        //     'filter'=>Html::activeDropDownList($searchModel, 'filter_id', $mainCategoryData,['class'=>'form-control','prompt' => 'All']),
                        // ],
                        [
                            'attribute'  => 'user_id',
                            'label' => 'Username',
                            'value' => function ($model) {
                                return Html::a($model->user->username , ['/user/view', 'id' => @$model->user->id]);
                            },
                            'filter'=>Html::activeDropDownList($searchModel, 'user_id', $userData,['class'=>'form-control','prompt' => 'All']),
                            'format'=>'raw'
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
                        [
                            'attribute'  => 'status',
                            'value'  => function ($data) {
                                return $data->getFilterStatus();
                            },
                            'filter'=>Html::activeDropDownList($searchModel, 'filter_id', $mainCategoryData,['class'=>'form-control','prompt' => 'All']),
                        ],
                        // 'created_at:datetime',
                        //  [
						// 	'class' => 'yii\grid\ActionColumn',
						// 	 'header' => 'Action',
                        //      'template' => '{update} {delete}',
                        //  ],
                    
                    ],
                    'tableOptions' => [
                        'id' => 'theDatatable',
                        'class' => 'table table-striped table-bordered table-hover',
                    ],
                ]); ?>
            </div>


        </div>
        <!-- /.box -->



        <!-- /.col -->
    </div>
</div>