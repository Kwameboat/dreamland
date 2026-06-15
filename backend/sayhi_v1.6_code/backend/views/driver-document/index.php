<?php

use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Driver Document';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <!-- /.box-header -->
            <div class="box-body">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        [
                            'attribute'  => 'user_id',
                            'label' => 'User',
                            'value' => function ($model) {
                                return @$model->user->name;
                            },
                        ],
                        [
                            'attribute' => 'is_dl_approved',
                            'value' => function ($model) {
                                return $model->is_dl_approved == 1 ? 'Yes' : 'No';
                            }
                        ],
                        [
                            'attribute' => 'is_rc_approved',
                            'value' => function ($model) {
                                return $model->is_rc_approved == 1 ? 'Yes' : 'No';
                            }
                        ],
                        [
                            'attribute' => 'is_vi_approved',
                            'value' => function ($model) {
                                return $model->is_vi_approved == 1 ? 'Yes' : 'No';
                            }
                        ],
                        [
                            'attribute' => 'is_vp_approved',
                            'value' => function ($model) {
                                return $model->is_vp_approved == 1 ? 'Yes' : 'No';
                            }
                        ],
                        [
							'class' => 'yii\grid\ActionColumn',
							 'header' => 'Action',
                             'template' => '{view} {update}',
                         ],
                    ],
                ]); ?>
            </div>


        </div>
        <!-- /.box -->



        <!-- /.col -->
    </div>
</div>