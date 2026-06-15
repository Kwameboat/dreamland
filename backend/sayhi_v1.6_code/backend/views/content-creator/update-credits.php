<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\User $model */

$this->title = 'Update Credits — ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Content Creators', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Credits';
?>
<div class="row">
    <div class="col-md-6">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Adjust creator credits</h3></div>
            <div class="box-body">
                <p>Current balance: <strong><?= (int) $model->available_coin ?></strong> credits</p>
                <p class="text-muted">Use a positive number to add credits, negative to deduct.</p>
                <?php $form = ActiveForm::begin(); ?>
                <?= $form->field($model, 'update_coin')->textInput(['type' => 'number'])->label('Credit adjustment') ?>
                <div class="form-group">
                    <?= Html::submitButton('Apply', ['class' => 'btn btn-success']) ?>
                    <?= Html::a('Cancel', ['view', 'id' => $model->id], ['class' => 'btn btn-default']) ?>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
