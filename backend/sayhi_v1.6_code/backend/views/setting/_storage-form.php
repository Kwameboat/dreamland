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
            <h4>Manage your file storage setting</h4>
        </div>
        <div class="panel-body">
            
            
            <?= $form->field($model, 'storage_system')->dropDownList($model->getStorageSystemDropDownData(),['id'=>"storage_system_id"]); ?>
            <div id="s3_container" class="key_container">

                <?php  echo  $form->field($model, 'aws_access_key_id')->textInput(['maxlength' => true]) ?>
                <?php  echo  $form->field($model, 'aws_secret_key')->textInput(['maxlength' => true]) ?>
                <?php  echo  $form->field($model, 'aws_region')->textInput(['maxlength' => true]) ?>
                <?php  echo  $form->field($model, 'aws_bucket')->textInput(['maxlength' => true]) ?>
                <?php  echo  $form->field($model, 'aws_access_url')->textInput(['maxlength' => true])->hint('Enter bucket URL (eg. https://[bucket_name].s3.amazonaws.com)') ?>
            </div>
            <div id="azure_container" class="key_container">
                <?php  echo  $form->field($model, 'azure_account_name')->textInput(['maxlength' => true]) ?>
                <?php  echo  $form->field($model, 'azure_account_key')->textInput(['maxlength' => true]) ?>
                <?php  echo  $form->field($model, 'azure_container')->textInput(['maxlength' => true]) ?>
            </div>
            <div id="wasabi_container" class="key_container">

                <?php  echo  $form->field($model, 'wasabi_access_key')->textInput(['maxlength' => true]) ?>
                <?php  echo  $form->field($model, 'wasabi_secret_key')->textInput(['maxlength' => true]) ?>
                <?php  echo  $form->field($model, 'wasabi_region')->textInput(['maxlength' => true]) ?>
                <?php  echo  $form->field($model, 'wasabi_bucket')->textInput(['maxlength' => true]) ?>
                <?php  echo  $form->field($model, 'wasabi_access_url')->textInput(['maxlength' => true])->hint('Enter endpoit URL (eg. https://s3.[bucket_region].wasabisys.com)') ?>
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

    $('#storage_system_id').change(function () {
        
        var smsGateway = $(this).val();
        //console.log(smsGateway);
        $(".key_container").slideUp();
        if(smsGateway==2){
            $("#s3_container").slideDown();
        }else if(smsGateway==3){
            $("#azure_container").slideDown();
        }else if(smsGateway==4){
            $("#wasabi_container").slideDown();
        }

    })

    $('#storage_system_id').trigger('change');
 })

JS;
    $this->registerJs($js, \yii\web\view::POS_READY);
    ?>