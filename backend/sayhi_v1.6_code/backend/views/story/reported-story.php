<?php

use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Reported Story';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12"><div class="box">
            <!-- /.box-header -->
            <div class="box-body">
                <!--<div class="pull-right m-bottom"><?= Html::a('Create', ['create'], ['class' => 'btn btn-success pull-right ']) ?></div>-->
                <div style="clear:both"></div>


                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                       
                        [
                            'attribute'  => 'user_id',
                            'label' => 'Username',
                            'value' => function($model){
                                
                                return Html::a(@$model->user->username, ['/user/view', 'id' => @$model->user_id]);
                            },
                            'filter'=>Html::activeDropDownList($searchModel, 'user_id', $userData,['class'=>'form-control','prompt' => 'All']),
                            'format'=>'raw'
                        ],
                        // 'description',
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
                                  
                                    $url = 'index.php?r=story/view-reported-story&id='.$model['id'];

                                    return $url;

                                }
 
                              

                            },
                         ],
                    
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