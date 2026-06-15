<?php

use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Tv Show';
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
                        [
                            'attribute'  => 'name',
                            'value' => function($model){
                                
                                return Html::a($model->name, ['/tv-show/view', 'id' => $model->id]);
                            },
                            'format'=>'raw'
                        ],
                        [
                            'attribute'  => 'category_id',
                            'value' => function($model){
                                return @$model->category->name;
                                
                                
                            },
                            'filter'=>Html::activeDropDownList($searchModel, 'category_id', $categoryData,['class'=>'form-control','prompt' => 'All']),
                            'format'=>'raw'
                        ],
                        [
                            'attribute'  => 'show_time',
                            'value'  => function ($model) {
                                return date('Y-m-d h:i a',$model->show_time);
                            },
                        ],
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
                             'template' => '{view} {update} {delete}',
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