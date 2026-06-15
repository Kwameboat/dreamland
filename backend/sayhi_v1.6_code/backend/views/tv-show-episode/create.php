<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */
// print_r($model->gettvShowName($_GET['tv_show_id'])->name);
$tvShowId = isset($_GET['tv_show_id']) ?  $_GET['tv_show_id'] : '';
$showName = '';
if(!empty($tvShowId)){
    $showName =   ': '.$model->gettvShowName($_GET['tv_show_id'])->name;
}
$this->title = 'Create Tv Show Episode'.$showName;
$this->params['breadcrumbs'][] = ['label' => 'Tv Show ', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-body">

                

                <?= $this->render('_form', [
                    'model' => $model,
                    'tvShowData'=>$tvShowData,                  
                ]) ?>

            </div>
        </div>
    </div>
</div>