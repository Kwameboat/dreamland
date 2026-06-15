<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'Interest Detail : '. $model->name;
$this->params['breadcrumbs'][] = ['label' => 'User', 'url' => ['index']];
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
            'username',
            'email',
            
             [
                'attribute'  => 'country_id',
                'value'  => function ($data) {
                    return $data->country->name;
                },
            ],
           // 'website',
            
           /* [
                'attribute'  => 'sex',
                'value'  => function ($data) {
                    return $data->getSex();
                },
            ],*/

            /*'phone',
            'address',
            'postcode',
            'country',
            'city',*/
            'last_active:datetime',
            'created_at:datetime',
            'updated_at:datetime'
        ],
    ]) ?>

</div>


</div>

</div>
</div>
