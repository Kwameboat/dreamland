<?php

use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Administrator';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12"><div class="box">
            <!-- <div class="box-header">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>

            </div>-->
            <!-- /.box-header -->
            <div class="box-body">
                
                <div class="pull-right"><?= Html::a('Create', ['create'], ['class' => 'btn btn-success pull-right']) ?></div>
                <div style="clear:both"></div>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        'name',    
                        'username',
                        'email',
                        [
                            'attribute'  => 'status',
                            'value'  => function ($data) {
                                return $data->getStatus();
                            },
                        ],
            
                        'created_at:datetime',
                        'last_active:datetime',


                        //['class' => 'yii\grid\ActionColumn'],
                        [
							'class' => 'yii\grid\ActionColumn',
							 'header' => 'Action',
                             'template' => '{update} {delete} {permission}',
                            // 'visible' => ($dataProvider->role == 1),
                            'urlCreator' => function ($action, $model, $key, $index) {

                                if($action === 'update') {
                                    $url = Url::to(['administrator/update', "id" => $model['id']]);
                                    return $url;
                
                                }
                                if($action === 'delete') {
                                    $url = Url::to(['administrator/delete', "id" => $model['id']]);
                                    return $url;
                
                                }
                
                                if($action === 'permission') {
                                    
                                    $url = Url::to(['administrator/auth-permission', "uid" => $model['id']]);
                                    return $url;
                
                                }
                
                            },
                            'buttons'=> [
                                'delete'=>function($url,$model,$key) {
                                    
                                    if (Yii::$app->user->id != $model->id) {
                                        
                                        //return Html::a( '<span class="glyphicon glyphicon-trash"></span>', $url);
                                        return Html::a('<span class="glyphicon glyphicon-trash"></span>',  $url, [
                                            //'class' => 'btn btn-danger',
                                            'data' => [
                                                'confirm' => 'Are you sure you want to delete this item?',
                                                'method' => 'post',
                                            ],
                                        ]);
                                
                                    
                                    } 
                
                                },
                                'update'=>function($url,$model,$key) {
                                    
                                    if (Yii::$app->user->id != $model->id) {
                                        
                                        return Html::a( '<span class="glyphicon glyphicon-pencil"></span>', $url);
                                    
                                    } 
                
                                },
                                'permission'=>function($url,$model,$key) {
                                    
                                    if (Yii::$app->user->id != $model->id && $model->role != 1 ) {
                                        
                                        return Html::a( '<span class="fa fa-lock"></span>', $url);
                                    
                                    } 
                
                                },
                
                            ],
                           
							 
						]
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