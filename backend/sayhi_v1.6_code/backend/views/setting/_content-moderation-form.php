<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="countryy-form">

    <?php $form = ActiveForm::begin(); ?>
    
    <?= $form->field($model, 'content_moderation_gateway')->dropDownList($model->getContentModerationDropDownData(),['id'=>"content_moderation_gateway_id"]) ?>
    <div id="sightengine_container" class="key_container">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>Sightengine (https://sightengine.com) </h4><i>Less then 60 sec. video allowed for nudity moderation</i>
        </div>
        <div class="panel-body">
            <?php  echo  $form->field($model, 'sightengine_api_user')->textInput(['maxlength' => true]) ?>
            <?php  echo  $form->field($model, 'sightengine_api_secret')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    </div>
    <div id="aws_container" class="key_container">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>Amazon Rekognition</h4>
        </div>
        <div class="panel-body">
            You must have set <a href="index.php?r=setting/storage">storage system</a> as  AWS S3  to use Amazone Rekognition. Please avoid if alreay setup.
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

    $('#content_moderation_gateway_id').change(function () {
        
        var smsGateway = $(this).val();
        //console.log(smsGateway);
        $(".key_container").slideUp();
        if(smsGateway==1){
            $("#sightengine_container").slideDown();
        }else if(smsGateway==2){
            $("#aws_container").slideDown();
        }

    })

    $('#content_moderation_gateway_id').trigger('change');
 })

JS;
    $this->registerJs($js, \yii\web\view::POS_READY);
    ?>