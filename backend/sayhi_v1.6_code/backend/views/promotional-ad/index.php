<?php

use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Promotional Ad';
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
                        [
                            'attribute'  => 'country_id',
                            'value'  => function ($data) {
                                return $data->country->name;
                            },
                        ],
                        'name',
                        [
                            'attribute'  => 'Display Status',
                            'value'  => function ($data) {
                                return $data->getDisplayStatus();
                            },
                        ],
                        [
                            'attribute'  => 'Date',
                            'value'  => function ($data) {
                                return $data->getActiveDate();
                            },
                        ],

                        [
                            'attribute'=>'image',
                            'value'=> function ($model) {
                                
                                 return Html::img($model->imageUrl, ['alt' => 'No Image', 'width' => '50px', 'height' => '50px']);
                                // return Html::img(Yii::$app->urlManagerFrontend->baseUrl.'/uploads/promotional-banner/thumb/'.$model->image, ['alt' => 'No Image', 'width' => '50px', 'height' => '50px']);
                            },
                            'format' => 'raw',
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