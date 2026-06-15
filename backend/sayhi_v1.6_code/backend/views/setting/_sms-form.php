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
            <h4>Select your SMS Gatway and fill the setting</h4>
        </div>
        <div class="panel-body">
            
            
            <?= $form->field($model, 'sms_gateway')->dropDownList($model->getSmsGatewayDropDownData(),['prompt' => 'Select','id'=>"sms_gateway_id"]); ?>
            <div id="twilio_container" class="key_container">
                <?php  echo  $form->field($model, 'twilio_sid')->textInput(['maxlength' => true]) ?>
                <?php  echo  $form->field($model, 'twilio_token')->textInput(['maxlength' => true]) ?>
                <?php  echo  $form->field($model, 'twilio_number')->textInput(['maxlength' => true])->hint('(eg. +14159697882)') ?>
            </div>
            <div id="sms91_container" class="key_container">
                <?php  echo  $form->field($model, 'msg91_authKey')->textInput(['maxlength' => true]) ?>
                <?php  echo  $form->field($model, 'msg91_sender_id')->textInput(['maxlength' => true]) ?>
            </div>
            <div id="vonage_container" class="key_container">
                <?php  echo  $form->field($model, 'vonage_api_key')->textInput(['maxlength' => true]) ?>
                <?php  echo  $form->field($model, 'vonage_api_secret')->textInput(['maxlength' => true]) ?>
            </div>

        </div>
    </div>

   
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
<?php
$js = <<<JS
 $(document).ready(function () {

    $('#sms_gateway_id').change(function () {
        
        var smsGateway = $(this).val();
        //console.log(smsGateway);
        $(".key_container").slideUp();
        if(smsGateway==1){
            $("#twilio_container").slideDown();
        }else if(smsGateway==2){
            $("#sms91_container").slideDown();
        }else if(smsGateway==4){
            $("#vonage_container").slideDown();
        }

    })

    $('#sms_gateway_id').trigger('change');
 })

JS;
    $this->registerJs($js, \yii\web\view::POS_READY);
    ?>