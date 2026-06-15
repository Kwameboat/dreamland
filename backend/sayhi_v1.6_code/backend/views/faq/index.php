<?php

use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'FAQs';
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
                        // 'Title',
                        // [
                        //     'attribute'  => 'title',
                        //     'value' => function($model){
                        //         return @$model->title;
                                
                                
                        //     },
                        //     // 'filter'=>Html::activeDropDownList($searchModel, 'title', $categoryData,['class'=>'form-control','prompt' => 'All']),
                        //     // 'format'=>'raw'
                        // ],
                        [
                            'attribute'  => 'question',
                            'value' => function($model){
                                return @$model->question;
                                
                                
                            },
                            // 'filter'=>Html::activeDropDownList($searchModel, 'question', $categoryData,['class'=>'form-control','prompt' => 'All']),
                            // 'format'=>'raw'
                        ],

                        [
                            'attribute'  => 'answer',
                            'value' => function($model){
                                return @$model->answer;
                                
                                
                            },
                            // 'filter'=>Html::activeDropDownList($searchModel, 'title', $categoryData,['class'=>'form-control','prompt' => 'All']),
                            // 'format'=>'raw'
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
                             'template' => '{update} {delete}',
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