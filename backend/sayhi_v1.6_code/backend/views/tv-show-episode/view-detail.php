<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'Show Episode Details : '.$model->name;
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
                    // 'tv_show_id', 
                    'episode_period',                                    
                    [
                        'attribute' => 'image',
                        'format' => 'html',    
                        'value' => function ($data) {
                            return Html::img($data->imageUrl, ['width' => '70px','height' => '60px']);
                        },
                    ],
                    [
                        'attribute' => 'video',
                        'format' => 'raw',    
                        'value' => function ($data) {
                            
                            
                                return '<video width="100" height="100" controls>
                                    <source src="' .$data->VideoUrl.'" type="video/mp4">
                                </video>';
                           
                           

                            // return Html::img($data->VideoUrl, ['width' => '70px','height' => '60px']);
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
