<?php

use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Polls';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12"><div class="box">
            <!-- /.box-header -->
            <div class="box-body">
                <div class="pull-right"><?= Html::a('Create', ['create'], ['class' => 'btn btn-success pull-right']) ?></div>
                <div style="clear:both"></div>


                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        'title',
                        [
                            'attribute'  => 'category_id',
                            'value' => function($model){
                                return @$model->category->name;
                                
                                
                            },
                            'filter'=>Html::activeDropDownList($searchModel, 'category_id', $categoryData,['class'=>'form-control','prompt' => 'All']),
                            'format'=>'raw'
                        ],  
                        // [
                        //     'attribute'  => 'campaigner_id',
                        //     'value' => function($model){
                        //         return @$model->organization->name;
                                
                                
                        //     },
                        //     'filter'=>Html::activeDropDownList($searchModel, 'campaigner_id', $organizationData,['class'=>'form-control','prompt' => 'All']),
                        //     'format'=>'raw'
                        // ],
                        'start_time:datetime',
                        'end_time:datetime',                      
                        
                        [
                            'attribute'  => 'status',
                            'value'  => function ($data) {
                                return $data->getStatus();
                            },
                        ],
                       
                         [
							'class' => 'yii\grid\ActionColumn',
							 'header' => 'Action',
                             'template' => '{update} {delete} {viewquest}',
                             'urlCreator' => function ($action, $model, $key, $index) {

                                if($action === 'update') {
                                    $url = Url::to(['poll/update', "id" => $model['id']]);
                                    return $url;
                
                                }
                                if($action === 'delete') {
                                    $url = Url::to(['poll/delete', "id" => $model['id']]);
                                    return $url;
                
                                }
                
                                if($action === 'viewquest') {
                                    // $url = Url::to(['poll-question/', "PollQuestionSearch[poll_id]" => $model['id']]);
                                    $url = Url::to(['poll/view', "id" => $model['id']]);
                                    return $url;
                
                                }
                
                            },
                
                            'buttons' => [
                
                                'viewquest' => function ($url, $model, $key) {
                
                                    return Html::a('<span class="fa fa-eye fa-lg"></span>', $url, ['title' => 'View Poll Questions']);
                
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