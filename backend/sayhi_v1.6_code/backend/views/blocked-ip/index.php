<?php

use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = "Blocked IP's";
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12"><div class="box">
            <!-- /.box-header -->
            <div class="box-body">
                <div class="pull-right"><?= Html::a('Add', ['create'], ['class' => 'btn btn-success pull-right']) ?></div>
                <div style="clear:both"></div>


                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        
                        [
                            'attribute'  => 'ip_address',
                            'value' => function($model){
                                return @$model->ip_address;
                                
                                
                            },
                            // 'filter'=>Html::activeDropDownList($searchModel, 'question', $categoryData,['class'=>'form-control','prompt' => 'All']),
                            // 'format'=>'raw'
                        ],

                        [
                            'attribute'  => 'description',
                            'value' => function($model){
                                return @$model->description;
                                
                                
                            },
                            // 'filter'=>Html::activeDropDownList($searchModel, 'title', $categoryData,['class'=>'form-control','prompt' => 'All']),
                            // 'format'=>'raw'
                        ],
                        'created_at:datetime',
 
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