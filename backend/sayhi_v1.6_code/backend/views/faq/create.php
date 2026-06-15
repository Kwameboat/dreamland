<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'FAQ';
$this->params['breadcrumbs'][] = ['label' => 'Create FAQ   ', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-body">

                

                <?= $this->render('_form', [
                    'model' => $model,
                    'categoryData'=>$categoryData
                   
                ]) ?>

            </div>
        </div>
    </div>
</div>