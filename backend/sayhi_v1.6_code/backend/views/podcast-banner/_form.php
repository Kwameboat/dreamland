<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use kartik\datetime\DateTimePicker;
use kartik\select2\Select2;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */
/* @var $form yii\widgets\ActiveForm */
//echo Yii::$app->urlManagerFrontend->baseUrl;
//echo  Url::base(true);
?>

<div class="countryy-form">

    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>    
    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
    <?php $model->bannerhiddenurl = Yii::$app->urlManager->createUrl(['podcast-banner/banner-reference', 'reference_id'=>$model->reference_id ,'id'=>'']); ?>
    <?= $form->field($model, 'bannerhiddenurl')->hiddenInput()->label(false); ?>
    <?=$form->field($model, 'banner_type')->dropDownList($model->getBannerType(),
    [
     'prompt' => 'Select Banner Type',
       'onchange' => ' $.post( "'.Yii::$app->urlManager->createUrl(['podcast-banner/banner-reference','id'=>'']).'"+$(this).val(), function( data ) {
        $( "select#podcastbanner-reference_id" ).html( data );
    });',

    ]);?>
    <?php 
    echo $form->field($model, 'reference_id')->widget(Select2::classname(), [
        'data' => $searchData,
        'language' => 'en',
        'theme' => Select2::THEME_DEFAULT,
        'options' => ['multiple' => false, 'placeholder' => 'Select size ..'],
        'pluginOptions' => [
            'allowClear' => true,
        ],
    ]);?>
    <?= $form->field($model, 'status')->dropDownList($model->getStatusDropDownData()); ?>
    
    <?= $form->field($model, 'imageFile')->fileInput() ?>
    <?php if(!$model->isNewRecord && $model->cover_image ){ ?>
    
    <p><?= Html::img($model->imageUrl, ['alt' => 'No Image', 'width' => '50px', 'height' => '50px']);?>
    </p>
    <?php }?>
    <?= $form->field($model, 'start_time')->widget(DateTimePicker::classname(), [
        'options' => ['placeholder' => 'Enter Start date ...'],
        //'size' => 'lg',
        'pluginOptions' => [
            'autoclose' => true,
            'format' => 'yyyy-mm-dd h:i',
        ],
    ]); ?>
       <?= $form->field($model, 'end_time')->widget(DateTimePicker::classname(), [
        'options' => ['placeholder' => 'Enter End date ...'],
        //'size' => 'lg',
        'pluginOptions' => [
            'autoclose' => true,
            'format' => 'yyyy-mm-dd h:i',
        ],
    ]); ?>
    
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>


<?php
$js=<<< JS
  //  alert('a')
        
  $(document).ready(function(){
    bannerType();
    $('#podcastbanner-banner_type').change(function(){
        var id = $(this).val();       
    });
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

function bannerType(){
    var id = $('#podcastbanner-banner_type').val();
    var url = $("#podcastbanner-bannerhiddenurl").val();
    var fullurl = url+id;
    $.post( fullurl, function( data ) {
    $( "select#podcastbanner-reference_id" ).html( data );
    });
    }
JS;
$this->registerJs($js,\yii\web\view::POS_READY);
?>
