<?php

use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Dating Subscription Packages';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12"><div class="box">
            <!-- /.box-header -->
            <div class="box-body">
                <div class="pull-right m-bottom"><?= Html::a('Create', ['create'], ['class' => 'btn btn-success pull-right ']) ?></div>
                <div style="clear:both"></div>


                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        'name',
                        'coin',
                        'number_of_profiles',
                        [
                            'attribute'  => 'duration',
                            'value' => function($model){
                                return $model->durationData;
                            }
                              
                        ],
                        [
                            'attribute'  => 'status',
                            'value'  => function ($data) {
                                return $data->statusString;
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