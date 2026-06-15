<?php

use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Matches';
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
                        //'name',
                        //'address',
                       
                        [
                            'attribute'  => 'match_type',
                            'label'  => 'Teams',
                            'value'  => function ($data) {
                                $teamStr=[];
                                foreach($data->matchTeam as $team){
                                    $teamStr[]= $team->name;
                                }
                                return implode('<br>',$teamStr);
                                
                                
                            },
                              'format'=>'raw'
                        ],
                        
                        [
                            'attribute'  => 'match_type',
                            'value'  => function ($data) {
                                return $data->getType();
                            },
                        ],
                       
                        [
                            'attribute'  => 'court_id',
                            'value'  => function ($data) {
                                return $data->court->name;
                            },
                        ],
                        'start_time:datetime',
                        [
                            'attribute'  => 'status',
                            'value'  => function ($data) {
                                return $data->getStatus();
                            },
                        ],
                       
                         [
							'class' => 'yii\grid\ActionColumn',
							 'header' => 'Action',
                             'template' => '{view} {delete}',
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