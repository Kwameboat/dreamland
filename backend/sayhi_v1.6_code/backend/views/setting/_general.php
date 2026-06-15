<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="countryy-form">

    <?php $form = ActiveForm::begin(); ?>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>General Setting</h4>
        </div>
        <div class="panel-body">
            
            <?php  echo  $form->field($model, 'release_version')->textInput(['maxlength' => true]) ?>
            <?php  echo  $form->field($model, 'website_name')->textInput(['maxlength' => true]) ?>
            <?php  echo  $form->field($model, 'website_url')->textInput(['maxlength' => true]) ?>
            <?php  echo  $form->field($model, 'google_play_store_url')->textInput(['maxlength' => true]) ?>
            <?php  echo  $form->field($model, 'apple_app_store_url')->textInput(['maxlength' => true]) ?>
            <?php  echo  $form->field($model, 'moments_name')->textInput(['maxlength' => true]) ?>
            <?php  echo  $form->field($model, 'music_url')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'ads_auto_approve')->dropDownList($model->getAutoAdsDropDownData()); ?>
            <?= $form->field($model, 'is_two_factor_auth')->dropDownList($model->getAutoAdsDropDownData())->hint('Please make sure you have updated right email in admin user before enable  this (<a href="index.php?r=administrator/profile">Update</a>)') ?>
        </div>
    </div>

   
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
<?php
$js = <<<JS

JS;
    $this->registerJs($js, \yii\web\view::POS_READY);
    ?>