<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>

<div class="countryy-form">

    <?php $form = ActiveForm::begin(); ?>
    
    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
    
    <?= $form->field($model, 'price')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'coin')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'in_app_purchase_id_ios')->textInput() ?>
    <?= $form->field($model, 'in_app_purchase_id_android')->textInput() ?>
    <?= $form->field($model, 'is_default')->dropDownList($model->getIsDefaultDropDownData()); ?>
    <?= $form->field($model, 'status')->dropDownList($model->getStatusDropDownData()); ?>
    
    
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
        var inputValue = $("input[type='radio']:checked"). val();
        console.log(inputValue)
        if(inputValue==2){
            $('#promotional_block').slideDown();
        }else{
            $('#promotional_block').slideUp();
            
        }
    }
    
    $('input[type="radio"]').click(function(){
        hideShowDiv();
       
    });
 //   hideShowDiv();

});
JS;
$this->registerJs($js,\yii\web\view::POS_READY);
?>