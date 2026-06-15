<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="countryy-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'email')->textInput() ?>
    <?php if($model->isNewRecord){ ?>
    <?= $form->field($model, 'password')->passwordInput() ?>
    <?= $form->field($model, 'confirmPassword')->passwordInput() ?>
    
    <?php } ?>
    <!--<?= $form->field($model, 'country_id')->dropDownList($countryData, ['prompt'=>'Select']); ?>

    <?= $form->field($model, 'address')->textInput() ?>
    <?= $form->field($model, 'phone')->textInput() ?>
    <?= $form->field($model, 'country')->textInput() ?>
    <?= $form->field($model, 'city')->textInput() ?>
    <?= $form->field($model, 'postcode')->textInput() ?>
    <?= $form->field($model, 'website')->textInput() ?>
    <?= $form->field($model, 'is_verified')->dropDownList($model->getVerifiedStatusDropDownData()); ?>
    -->
    <?= $form->field($model, 'status')->dropDownList($model->getStatusDropDownData()); ?>
    <?= $form->field($model, 'is_verified')->dropDownList($model->getVerifiedStatusDropDownData()); ?>
    <?= $form->field($model, 'imageFile')->fileInput() ?>
    <?php if(!$model->isNewRecord && $model->image ){ 
        
        
        ?>
    
    <p><?php 
    echo  Html::img(Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_USER,$model->image), ['alt' => 'No Image', 'width' => '50px', 'height' => '50px']);
    //echo  Html::img(Yii::$app->params['pathUploadUser'].'/'.$model->image, ['alt' => 'No Image', 'width' => '50px', 'height' => '50px']);
    ?>
    </p>
    <?php }?>
    
   

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
