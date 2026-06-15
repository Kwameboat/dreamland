<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'Podcast Show Episode List : '.$tvShowData->name;
$this->params['breadcrumbs'][] = $this->title;
?>


<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <!-- <div class="box-header">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>

            </div>
             -->
            <div class="box-body">
            <div class="pull-right"><?= Html::a('Create Episode', ['create','show_id' => $tvShowData->id], ['class' => 'btn btn-success pull-right']) ?></div>
                <div style="clear:both"></div>


            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    'name',

                    [
                        'attribute' => 'image',
                        'format' => 'html',    
                        'value' => function ($data) {
                            return Html::img($data->imageUrl, ['width' => '70px','height' => '60px']);
                        },
                    ],
                    
                    'episode_period',
                    [
                        'attribute'  => 'status',
                        'value'  => function ($data) {
                            return $data->getStatus();
                        },
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


</div>

</div>
</div>
