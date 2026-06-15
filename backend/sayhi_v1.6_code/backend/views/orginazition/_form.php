<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

?>

<div class="countryy-form">
    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
    <?= $form->field($model, 'name')->textInput() ?>
    <?= $form->field($model, 'type')->dropDownList($categoryData,['prompt' => 'Select']); ?>
    <?= $form->field($model, 'phone')->textInput() ?>  
    <?= $form->field($model, 'address')->textInput() ?>
    <?= $form->field($model, 'email')->textInput() ?>
    <?= $form->field($model, 'status')->dropDownList($model->getStatusDropDownData()); ?>
    
    <?= $form->field($model, 'image')->fileInput() ?>
    
    <?php if(!$model->isNewRecord && $model->image ){ ?>
    <p><?= Html::img($model->imageUrl, ['alt' => 'No Image', 'width' => '50px', 'height' => '50px']);?>
    </p>
    <?php }?>


   


    <?= $form->field($model, 'description')->textarea() ?>
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
