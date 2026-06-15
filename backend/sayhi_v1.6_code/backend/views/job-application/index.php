<?php

use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Job Applications';
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
                        [
                            'attribute'  => 'job_id',
                            'value' => function($model){
                                return @$model->job->title;
                                
                                
                            },
                            'filter'=>Html::activeDropDownList($searchModel, 'job_id', $jobData,['class'=>'form-control','prompt' => 'All']),
                            'format'=>'raw'
                        ],
                        [
                            'attribute'  => 'user_id',
                            'label'      => 'Username',
                            'value' => function($model){
                                
                                return Html::a(@$model->userDetails->username, ['/user/view', 'id' => $model->user_id]);
                            },
                            // 'filter'=>Html::activeDropDownList($searchModel, 'user_id', $userData,['class'=>'form-control','prompt' => 'All']),
                            'format'=>'raw'
                        ],
                        // [
                        //     'attribute'  => 'user_id',
                        //     'label'=> 'User',
                        //     'value'  => function ($data) {
                        //         return $data->userDetails->name;
                        //     },
                        // ],
                        'total_experience',
                        'education',
                        'created_at:datetime',
                        
                        [
                            'attribute'  => 'status',
                            'value'  => function ($data) {
                                return $data->getStatus();
                            },
                        ],
                       
                         [
							'class' => 'yii\grid\ActionColumn',
							 'header' => 'Action',
                             'template' => '{view} {update}',
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