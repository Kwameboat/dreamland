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
            <h4>App Settings</h4>
        </div>
        <div class="panel-body">
            
            <?= $form->field($model, 'maximum_video_duration_allowed')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'free_live_tv_duration_to_view')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'latest_app_download_link')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'disclaimer_url')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'privacy_policy_url')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'terms_of_service_url')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'giphy_api_key')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'agora_api_key')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'agora_app_certificate')->textInput(['maxlength' => true]) ?>
            
            <?php // echo  $form->field($model, 'google_map_api_key')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'interstitial_ad_unit_id_for_android')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'interstitial_ad_unit_id_for_IOS')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'reward_interstitl_ad_unit_id_for_android')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'reward_interstitial_ad_unit_id_for_IOS')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'banner_ad_unit_id_for_android')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'banner_ad_unit_id_for_IOS')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'fb_interstitial_ad_unit_id_for_android')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'fb_interstitial_ad_unit_id_for_IOS')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'fb_reward_interstitial_ad_unit_id_for_android')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'fb_reward_interstitial_ad_unit_id_for_IOS')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'network_to_use')->dropDownList($model->getNetworkUseDropDownData()); ?>
            <?= $form->field($model, 'chat_gpt_key')->textInput(['maxlength' => true]) ?>
            <!--<?= $form->field($model, 'imgly_key')->textInput(['maxlength' => true])->label('Photo Video editor key (Img.ly key. <a href="https://img.ly">https://img.ly/)</a>') ?>-->

        </div>
    </div>

   

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
<?php
$js = <<<JS
    // alert('a')
   
  

JS;
    $this->registerJs($js, \yii\web\view::POS_READY);
    ?>