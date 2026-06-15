<?php

use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Current running promotion';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <!-- /.box-header -->
            <div class="box-body">
            <?php
            // echo "<pre>";
            // print_r($dataProvider);
            // exit;

            ?>


                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        [
                            'attribute'  => 'created_by',
                            'label' => 'Promotion Created By',
                            'value' => function ($model) {
                                return Html::a($model->user->name , ['/user/view', 'id' => @$model->user->id]);
                                // return @$model->user->name;
                            },
                            'format'=>'raw'
                        ],
                        [
                            'attribute'  => 'post_id',
                            'label' => 'Post Title',
                            'value' => function ($model) {
                                return Html::a(@$model->post->title , ['/post/view', 'id' => @$model->post->id]);
                            },
                            'format'=>'raw'
                        ],
                        [
                            'attribute'  => 'audience_id',
                            'label' => 'Audience Name',
                            'value' => function ($model) {
                                return @$model->audience->name;
                            },
                        ],
                        'created_at:datetime',
                        'expiry:datetime',
                        [
                            'attribute'  => 'status',
                            'label' => 'Status',
                            'value' => function ($model) {
                                return @$model->getStatus();
                            },
                        ],
                        [
							'class' => 'yii\grid\ActionColumn',
							 'header' => 'Action',
                             'template' => '{view}',
                         ],
                    ],
                ]); ?>
            </div>


        </div>
        <!-- /.box -->



        <!-- /.col -->
    </div>
</div>