<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'Profile';

\yii\web\YiiAsset::register($this);
?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
         <div class="box-body">



    <p>
        <?= Html::a('Update', ['update-profile', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        
        <?= Html::a('Change Password', ['change-password'], ['class' => 'btn btn-primary']) ?>
        
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'name',
            'username',
            'email',
            'last_active:datetime',
            'created_at:datetime',
            'updated_at:datetime'
        ],
    ]) ?>

</div>


</div>

</div>
</div>
