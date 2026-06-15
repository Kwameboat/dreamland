<?php

use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Live Gift History';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12"><div class="box">
            <!-- /.box-header -->
            <div class="box-body">
               
                <div style="clear:both"></div>


                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],                       
                       [
                        'attribute'  => 'reciever_id',
                        'label' => 'Gift Reciever',
                        'value' => function ($model) {
                            return Html::a($model->user->name , ['/user/view', 'id' => @$model->user->id]);
                        },
                        'filter'=>Html::activeDropDownList($searchModel, 'reciever_id', $userData,['class'=>'form-control','prompt' => 'All']),
                        'format'=>'raw'
                       ],
                       [
                        'attribute'  => 'sender_id',
                        'label' => 'Gift Sender',
                        'value' => function ($model) {
                            return Html::a($model->senderUser->name , ['/user/view', 'id' => @$model->user->id]);
                        },
                        'format'=>'raw'
                    ],
                     
                    //    'gift_id',
                        'coin',
                        'coin_actual',
                        'created_at:datetime',
                       
                         [
							'class' => 'yii\grid\ActionColumn',
							 'header' => 'Action',
                             'template' => '{view}',
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