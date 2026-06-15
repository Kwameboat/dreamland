<?php

use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Tv Show Episode';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12"><div class="box">
            <!-- /.box-header -->
            <div class="box-body">
                <div class="pull-right"><?= Html::a('Create', ['create'], ['class' => 'btn btn-success pull-right']) ?></div>
                <div style="clear:both"></div>
                <?php 
                ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        [
                            'attribute'  => 'name',
                            'value' => function($model){
                                
                                return Html::a($model->name, ['/tv-show-episode/view-detail', 'id' => $model->id]);
                            },
                            'format'=>'raw'
                        ],
                        [
                            'attribute'  => 'tv_show_id',
                            'value' => function($data){
                                
                                return Html::a($data->showName->name, ['/tv-show/view', 'id' => $data->tv_show_id]);
                            },
                            'format'=>'raw'
                        ],
                        'episode_period',
                        [
                            'attribute' => 'image',
                            'format' => 'html',    
                            'value' => function ($data) {
                                return Html::img($data->imageUrl, ['width' => '70px','height' => '60px']);
                            },
                        ],
                        
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
                                    $url = Url::to(['tv-show-episode/update', "id" => $model['id']]);
                                    return $url;
                
                                }
                                if($action === 'delete') {
                                    $url = Url::to(['tv-show-episode/delete', "id" => $model['id']]);
                                    return $url;
                
                                }
                
                                if($action === 'viewdetail') {
                                    $url = Url::to(['tv-show-episode/view-detail', "id" => $model['id']]);
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
                ]); ?>
            </div>


        </div>
        <!-- /.box -->



        <!-- /.col -->
    </div>
</div>