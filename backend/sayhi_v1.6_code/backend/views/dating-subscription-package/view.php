<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'Dating Subscription Package Detail : '. $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Package', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>


<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <!-- <div class="box-header">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>

            </div>
             -->
            <div class="box-body">



    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'name',
            // 'price',
            'coin',
            'number_of_profiles',
            [
                'attribute'  => 'duration',
                'value' => function($model){
                    return $model->durationData;
                }
                  
            ],
            // 'in_app_purchase_id_ios',
            // 'in_app_purchase_id_android',
            // [
            //     'attribute'  => 'is_default',
            //     'value' => function($model){
            //         return $model->isDefaultString;
            //     }
                  
            // ],
            [
                'attribute'  => 'status',
                'value' => function($model){
                    return $model->statusString;
                }
                  
            ],
            
           // 'website',
          //  'last_active:datetime',
            'created_at:datetime',
            'updated_at:datetime'
        ],
    ]) ?>

</div>


</div>

</div>
</div>
