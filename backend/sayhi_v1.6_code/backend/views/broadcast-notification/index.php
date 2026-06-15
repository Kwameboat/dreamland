<?php
//use yii\grid\GridView;
use kartik\grid\GridView;
use yii\helpers\Html;
use yii\grid\CheckboxColumn;
use common\models\DreamlandAudience;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */


$this->title = 'Broadcast Notifications';
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
                <div class="pull-right">
                <?= Html::a('Send to viewers', ['create', 'audience' => DreamlandAudience::VIEWERS], ['class' => 'btn btn-default pull-right','style' => 'margin-bottom:5px;margin-right:5px;']) ?>
                <?= Html::a('Send to creators', ['create', 'audience' => DreamlandAudience::CREATORS], ['class' => 'btn btn-default pull-right','style' => 'margin-bottom:5px;margin-right:5px;']) ?>
                <?= Html::a('Send broadcast', ['create'], ['class' => 'btn btn-success pull-right','style' => 'margin-bottom:5px']) ?>
                    
                </div>
                <div style="clear:both"></div>

                <?= GridView::widget([
                    'id' => 'grid',
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [

                        ['class' => 'kartik\grid\SerialColumn'],

                        'title',
                        'message_body',
                        [
                            'attribute' => 'audience_type',
                            'label' => 'Audience',
                            'value' => static fn($model) => DreamlandAudience::label((string) ($model->audience_type ?: DreamlandAudience::CUSTOM)),
                        ],
                        [
                            'attribute'  => 'total_user',
                            'value' => function($model){
                                
                                return Html::a(@$model->total_user, ['/broadcast-notification/broadcast-user', 'id' => $model->id],['title'=>'View Users']);
                            },
                            'format'=>'raw'
                        ],
                        
                        'created_at:datetime',



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
                    //'toggleDataContainer' => ['class' => 'btn-group-sm'],
                    //'exportContainer' => ['class' => 'btn-group-sm'],
                    'exportConfig' => [
                        GridView::CSV => ['label' => 'CSV'],
                        GridView::EXCEL => [],
                        // html settings],
                
                    ],


                    /*
                    'toolbar' =>  [
                        ['content'=>
                            Html::button('&lt;i class="glyphicon glyphicon-plus">&lt;/i>', ['type'=>'button', 'title'=>Yii::t('app', 'Add Book'), 'class'=>'btn btn-success', 'onclick'=>'alert("This will launch the book creation form.\n\nDisabled for this demo!");']) . ' '.
                            Html::a('&lt;i class="glyphicon glyphicon-repeat">&lt;/i>', ['grid-demo'], ['data-pjax'=>0, 'class' => 'btn btn-default', 'title'=>Yii::t('app', 'Reset Grid')])
                        ],
                    ],*/

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
