<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>

<div class="countryy-form">

    <?php $form = ActiveForm::begin(); ?>
    
    
    <?= $form->field($model, 'status')->dropDownList($model->getStatusDropDownData()); ?>
    
    
    
    <!--<?= $form->field($model, 'category_id')->dropDownList($mainCategoryDataList,['prompt'=>'Select']); ?>-->
    
    <?= $form->field($model, 'category_id')->dropDownList($mainCategoryDataList, [
                                                'prompt' => 'Select Category',
                                                'class'=>'form-control',
                                                'onchange'=>'
                                                            $.post( "'.Yii::$app->urlManager->createUrl(['ad/sub-category-lists','id'=>'']).'"+$(this).val(), function( data ) {
                                                                $( "select#ad-sub_category_id" ).html( data );
                                                            });
                                                    '
                                                ]); ?>


    <?= $form->field($model, 'sub_category_id')->dropDownList($subCategoryDataList,['prompt'=>'Select Sub Category']); ?>
    
    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
    <?php /* ?>
          <?= $form->field($model, 'country_id')->dropDownList($countryDataList, [
                                    'prompt' => 'Select Country',
                                    'class'=>'form-control',
                                    'onchange'=>'
                                                $.post( "'.Yii::$app->urlManager->createUrl(['ad/state-lists','id'=>'']).'"+$(this).val(), function( data ) {
                                                    $( "select#ad-state_id" ).html( data );
                                                });
                                        '
                                    ]); ?>

    <?= $form->field($model, 'state_id')->dropDownList($stateDataList, [
                                    'prompt' => 'Select State',
                                    'class'=>'form-control',
                                    'onchange'=>'
                                                $.post( "'.Yii::$app->urlManager->createUrl(['ad/city-lists','id'=>'']).'"+$(this).val(), function( data ) {
                                                    $( "select#ad-city_id" ).html( data );
                                                });
                                        '
                                    ]); ?>


    <?= $form->field($model, 'city_id')->dropDownList($cityDataList,['prompt'=>'Select']); ?>
<?php */ ?>
    
    <?= $form->field($model, 'description')->textArea(['rows'=>10]) ?>
    <?= $form->field($model, 'package_banner_id')->dropDownList($promotionalBanner,['prompt'=>'No Package Selected']); ?>
    
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