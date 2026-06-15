<?php
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\CreatorForm $model */

$this->title = 'Edit Creator: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Content Creators', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Edit';
?>
<div class="row">
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Edit creator profile</h3></div>
            <div class="box-body">
                <?= $this->render('_form', ['model' => $model]) ?>
            </div>
        </div>
    </div>
</div>
