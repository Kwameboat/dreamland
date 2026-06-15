<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\data\ArrayDataProvider;
use kartik\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'View audio Detail';
$this->params['breadcrumbs'][] = ['label' => 'Audio', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
//\yii\web\YiiAsset::register($this);
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box">

            <div class="box-body">
                <p>

                 <?=
                    
                    DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            [
                                'attribute'  => 'category_id',
                                'value'  => function ($data) {
                                    return @$data->category->name;
                                },
                            ],
                            'name',
                            'artist',
                            'duration',
                            'created_at:datetime',
                            'created_by',
                            'updated_at:datetime',
                            'updated_by',
                            [
                                'attribute'  => 'status',
                                'value'  => function ($data) {
                                    return @$data->getStatus();
                                },
                            ],
                            
                            [
                                'attribute'=>'image',
                                'value'=> function ($model) {                                    
                                     return Html::img($model->imageUrl, ['alt' => 'No Image', 'width' => '50px', 'height' => '50px']);
                                },
                                'format' => 'raw',
                             ],
                             [
                                'attribute' => 'audio',
                                'format' => 'raw',    
                                'value' => function ($data) {
                                  return  '<audio controls>
                                    <source src="horse.ogg" type="audio/ogg">
                                    <source src="'.$data->audioUrl.'" type="audio/mpeg">
                                    Your browser does not support the audio element.
                                </audio>';
                                
                                },
                            ],
                            
                           
                        ],
                    ]) ?>
                </p>
            </div>


        </div>

    </div>
</div>