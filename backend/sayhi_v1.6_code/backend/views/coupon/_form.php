<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use kartik\date\DatePicker;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */
/* @var $form yii\widgets\ActiveForm */
//echo Yii::$app->urlManagerFrontend->baseUrl;
//echo  Url::base(true);
?>

<div class="countryy-form">

    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
    <?= $form->field($model, 'business_id')->dropDownList($businessData,['prompt' => 'Select']); ?>
    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'code')->textInput(['maxlength' => true,'style' => 'text-transform:uppercase']) ?>
    <?= $form->field($model, 'website_url')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'start_date')->widget(DatePicker::classname(), [
        'options' => ['placeholder' => 'Enter Show Time date ...'],
        //'size' => 'lg',
        'pluginOptions' => [
            'autoclose' => true,
            'format' => 'yyyy-mm-dd',
        ],
    ]); ?> 
    <?= $form->field($model, 'expiry_date')->widget(DatePicker::classname(), [
        'options' => ['placeholder' => 'Enter Show Time date ...'],
        //'size' => 'lg',
        'pluginOptions' => [
            'autoclose' => true,
            'format' => 'yyyy-mm-dd',
        ],
    ]); ?> 
    <?= $form->field($model, 'status')->dropDownList($model->getStatusDropDownData()); ?>

    
    
    
    <?= $form->field($model, 'imageFile')->fileInput() ?>
    <?php if(!$model->isNewRecord && $model->image ){ ?>
    
    <p><?= Html::img($model->imageUrl, ['alt' => 'No Image', 'width' => '50px', 'height' => '50px']);?>
    </p>
    <?php }?>

    <?= $form->field($model, 'description')->textArea(['maxlength' => true,'rows'=>6]) ?>
    
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>


<?php
$js=<<< JS
  //  alert('a')
        
  $(document).ready(function(){

});
JS;
$this->registerJs($js,\yii\web\view::POS_READY);
?>
