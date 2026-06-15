<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */
$showId = isset($_GET['show_id']) ?  $_GET['show_id'] : '';
$showName = '';
if(!empty($showId)){
    $showName =   ': '.$model->getPodcastShowName($_GET['show_id'])->name;
}
$this->title = 'Create Podcast Show Episode'.$showName;
$this->params['breadcrumbs'][] = ['label' => 'Host Show ', 'url' => ['index']];
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