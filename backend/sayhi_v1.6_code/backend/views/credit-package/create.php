<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var common\models\CreditPackage $model */

$this->title = 'Create Credit Package';
?>
<div class="credit-package-create box box-primary">
    <div class="box-body">
        <?php $form = ActiveForm::begin(); ?>
        <?= $form->field($model, 'credit_amount')->input('number') ?>
        <?= $form->field($model, 'fiat_cost')->input('number', ['step' => '0.01']) ?>
        <?= $form->field($model, 'currency')->textInput(['maxlength' => true, 'value' => $model->currency ?: 'GHS']) ?>
        <?= $form->field($model, 'is_active')->checkbox(['checked' => true]) ?>
        <div class="form-group">
            <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
