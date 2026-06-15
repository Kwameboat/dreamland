<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'Jobs Detail By User : '. $model->userDetails->name;
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
            </p>
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            
             [
                'attribute'  => 'user_id',
                'label'=> 'Name',
                'value'  => function ($data) {
                    return $data->userDetails->name;
                },
            ],
            [
                'attribute'  => 'user_id',
                'label'=> 'Email',
                'value'  => function ($data) {
                    return $data->userDetails->email;
                },
            ],
            [
                'attribute'  => 'user_id',
                'label'=> 'Phone Number',
                'value'  => function ($data) {
                    return $data->userDetails->phone;
                },
            ],
           'total_experience',
           'education',
           'cover_letter',
        //    'resume',   
           [
            'attribute' => 'resume',
            'format' => 'raw',    
            'value' => function ($data) {
                return Html::a('View Resume', $data->resumeUrl, ['target' => '_blank']);

            },
        ],      
           [
            'attribute'  => 'status',
            'value'  => function ($data) {
                return $data->getStatus();
            },
        ],

            /*'phone',
            'address',
            'postcode',
            'country',
            'city',*/
            // 'last_active:datetime',
            'created_at:datetime',
            'updated_at:datetime'
        ],
    ]) ?>

</div>


</div>

</div>
</div>
