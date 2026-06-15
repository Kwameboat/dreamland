<?php
//use yii\grid\GridView;
use kartik\grid\GridView;
use yii\helpers\Html;
use yii\grid\CheckboxColumn;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */


$this->title = 'Broadcast Notification Receiver';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <!-- <div class="box-header">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>

            </div>-->
            <!-- /.box-header -->
            <div class="box-body">
                <div style="clear:both"></div>

                <?= GridView::widget([
                    'id' => 'grid',
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [

                        ['class' => 'kartik\grid\SerialColumn'],

                        
                      
                        [
                            'attribute'  => 'Name',
                            'value' => function($data){
                                
                                return Html::a(@$data->user->name, ['/user/view', 'id' => $data->user->id]);
                            },
                            'format'=>'raw'
                        ],
                        [
                            'attribute' => 'username',
                            'value' => function ($data) {
                                return $data->user->username;
                            },
                        ]

                    ],
                    'tableOptions' => [
                        'id' => 'theDatatable',
                        'class' => 'table table-striped table-bordered table-hover',
                    ],
                    'toolbar' => [

                        [

                        ],
                        //'{export}',
                        //'{toggleData}'
                    ],
                  

                    'pjax' => false,
                    'bordered' => true,
                    'striped' => false,
                    'condensed' => false,
                    'responsive' => true,
                    'hover' => true,
                    'floatHeader' => false,
                    //'floatHeaderOptions' => ['top' => $scrollingTop],
                    'showPageSummary' => false,
                    'panel' => [
                        // 'type' => GridView::TYPE_PRIMARY
                    ],

                ]); ?>
            </div>


        </div>
        <!-- /.box -->



        <!-- /.col -->
    </div>
</div>
