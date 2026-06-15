<?php

use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'User post reels';
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
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        [
                            'attribute'  => 'title',
                            'label' => 'Title',
                            'value' => function ($model) {
                                return Html::a(@$model->title , ['/post/view', 'id' => @$model->id]);
                            },
                            'format'=>'raw'
                        ],
                        [
                            'attribute'  => 'user_id',
                            'label' => 'User',
                            'value' => function ($model) {
                                return Html::a($model->user->name , ['/user/view', 'id' => @$model->user->id]);
                            },
                            'filter'=>false,
                            'format'=>'raw'
                        ],
                        
                        [
                            'attribute'  => 'audio_id',
                            'value'  => function ($data) {
                                return Html::a(@$data->audioName->name , ['/audio/audio-details', 'audio_id' => @$data->audio_id]);

                            },
                            'format'=>'raw'
                        ],
                        'audio_start_time',
                        'audio_end_time',
                        'total_view',
                        'total_like',
                        'total_comment',
                        'total_share',
                        [
                            'attribute'  => 'status',
                            'value'  => function ($data) {
                                return @$data->getStatus();
                            },
                        ],
                      
                        //  [
						// 	'class' => 'yii\grid\ActionColumn',
						// 	 'header' => 'Action',
                        //      'template' => '{update} {delete}',
                        //  ],
                    
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