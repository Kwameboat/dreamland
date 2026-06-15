<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */
/* @var $form yii\widgets\ActiveForm */
//echo Yii::$app->urlManagerFrontend->baseUrl;
//echo  Url::base(true);
?>

<div class="countryy-form">

    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
  
    <?php echo $model->user->name;
    
    
    ?>
                            
    <?= $form->field($model, 'dl_card_number')->textInput() ?>
    <?= $form->field($model, 'dl_expiry_date')->textInput() ?>
    <label class="control-label" for="driverdocument-dl_expiry_date">Driving License</label>
    <br/>
    <?=  

    
Html::img(@$model->dlImageUrl  , ['width' => '70px', 'height' => '60px']); ?>
                              
    <?= $form->field($model, 'is_dl_approved')->dropDownList($model->getIsDlApprovedDropDownData()); ?>
    <label class="control-label" for="driverdocument-dl_expiry_date">RC </label>
    <br/>
    <?= 
    
      Html::img(@$model->rcImageUrl  , ['width' => '70px', 'height' => '60px']);
    
    ?>
       
    <?= $form->field($model, 'is_rc_approved')->dropDownList($model->getIsRcApprovedDropDownData()); ?>
    <label class="control-label" for="driverdocument-dl_expiry_date">Vehicle Insurance </label>
    <br/>
    <?= Html::img(@$model->viImageUrl  , ['width' => '70px', 'height' => '60px']); ?>
       
    <?= $form->field($model, 'is_vi_approved')->dropDownList($model->getIsViApprovedDropDownData()); ?>
    <label class="control-label" for="driverdocument-dl_expiry_date">Vehicle Premit </label>
    <br/>
    <?=  Html::img(@$model->vpImageUrl  , ['width' => '70px', 'height' => '60px']); ?>
       
    <?= $form->field($model, 'is_vp_approved')->dropDownList($model->getIsVpApprovedDropDownData()); ?>
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>
 
