<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="countryy-form">

    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
   
    <?= $form->field($model, 'title')->textArea(['maxlength' => true])->label('Title') ?>
    <?= $form->field($model, 'is_comment_enable')->dropDownList($model->getCommonDropDownData()); ?>
    <?= $form->field($model, 'status')->dropDownList($model->getStatusDropDownData()); ?>    
    
    <?= $form->field($model, 'imageFile[]')->fileInput(['multiple' => true])->label('Media File(s)') ?>
    <?php /* if(!$model->isNewRecord && $model->image ){ ?>
    
    <p><?php // Html::img($model->imageUrl, ['alt' => 'No Image', 'width' => '50px', 'height' => '50px']);?>
    </p>
    <?php } */ ?>
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
