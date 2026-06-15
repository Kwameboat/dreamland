<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'Show Episode';
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
            <div class="pull-right"><?= Html::a('Create Episode', ['create'], ['class' => 'btn btn-success pull-right']) ?></div>
                <div style="clear:both"></div>


            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    'name',
                    [
                        'attribute'  => 'category_id',
                        'value' => function($model){
                            return @$model->category->name;
                            
                            
                        },
                        'filter'=>Html::activeDropDownList($searchModel, 'category_id', $categoryData,['class'=>'form-control','prompt' => 'All']),
                        'format'=>'raw'
                    ],

                    [
                        'attribute' => 'image',
                        'format' => 'html',    
                        'value' => function ($data) {
                            return Html::img($data->imageUrl, ['width' => '70px','height' => '60px']);
                        },
                    ],
                    [
                        'attribute'  => 'status',
                        'value'  => function ($data) {
                            return $data->getStatus();
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


</div>

</div>
</div>
