<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use kartik\date\DatePicker;
use kartik\datetime\DateTimePicker;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */
/* @var $form yii\widgets\ActiveForm */
//echo Yii::$app->urlManagerFrontend->baseUrl;
//echo  Url::base(true);
?>

<div class="countryy-form">

    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
    <?= $form->field($model, 'tv_channel_id')->dropDownList($channelData,['prompt' => 'Select']); ?>
    <?= $form->field($model, 'category_id')->dropDownList($categoryData,['prompt' => 'Select']); ?>
    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'age_group')->dropDownList(['PG'=>'PG' , '12A+' => '12A+', '15+' => '15+', '18+' =>'18+'],['prompt'=>'Select Age']); ?>
    <?= $form->field($model, 'language')->dropDownList($languageData,['prompt' => 'Select']); ?>
    <?= $form->field($model, 'status')->dropDownList($model->getStatusDropDownData()); ?>
   
   
   <?= $form->field($model, 'show_time')->widget(DateTimePicker::classname(), [
        'options' => ['placeholder' => 'Enter Show Time date ...'],
        //'size' => 'lg',
        'pluginOptions' => [
            'autoclose' => true,
            'format' => 'yyyy-mm-dd h:i',
        ],
    ]); ?>
    
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
    function hideShowDiv() {
        var inputValue = $("#livetv-is_paid"). val();
        
        if(inputValue==1){
            
            $('#coin_text_block').slideDown();
        }else{
            
            $('#coin_text_block').slideUp();
            
        }
    }
    
    $('#livetv-is_paid').change(function(){
        hideShowDiv();
       
    });
    hideShowDiv();

});
JS;
$this->registerJs($js,\yii\web\view::POS_READY);
?>
