<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'Podcast Show Episode Details : '.$model->name;
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
            
                <div style="clear:both"></div>


                <?= DetailView::widget([
                'model' => $model,
                'attributes' => [           
                    'name',
                    'episode_period',                                    
                    [
                        'attribute' => 'image',
                        'format' => 'html',    
                        'value' => function ($data) {
                            return Html::img($data->imageUrl, ['width' => '70px','height' => '60px']);
                        },
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
                    [
                        'attribute'  => 'status',
                        'value' => function($model){
                            return $model->getStatus();
                        },
                        'format'=>'raw'
                        
                    ],
                    'created_at:datetime',        
                ],
            ]) ?>
</div>

</div>


</div>

</div>
</div>
