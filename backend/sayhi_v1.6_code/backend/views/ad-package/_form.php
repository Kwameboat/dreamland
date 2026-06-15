<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>

<div class="countryy-form">

    <?php $form = ActiveForm::begin(); ?>
    <!--<?= $form->field($model, 'type')->radioList( $model->getPackageTypeData(),['class'=>'type_radio'])?>
    <div id="promotional_block" style="display:<?=($model->type==1)?'none':'block'?>"> 
    <?= $form->field($model, 'promotional_banner_id')->dropDownList($promotionalBannerData,['prompt'=>'Select']); ?>
    </div>-->
    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
    
    
    <?= $form->field($model, 'term')->dropDownList($model->getTermDropDownData(),['prompt'=>'Select']); ?>
    
    <?= $form->field($model, 'price')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'ad_limit')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'ad_duration')->textInput() ?>
    <?= $form->field($model, 'in_app_purchase_id_ios')->textInput() ?>
    <?= $form->field($model, 'in_app_purchase_id_android')->textInput() ?>

    <?= $form->field($model, 'is_default')->dropDownList($model->getIsDefaultDropDownData()); ?>
    <?= $form->field($model, 'status')->dropDownList($model->getStatusDropDownData()); ?>
    
 

    <div class="panel panel-default">
            <div class="panel-heading">
                <h4>Feature Ad Setting</h4>
            </div>
            <div class="panel-body">


            <?= $form->field($model, 'featured_duration')->textInput() ?>
            <?= $form->field($model, 'featured_fee')->textInput() ?>
            
                    
            </div>
        </div>



    <div class="panel panel-default">
            <div class="panel-heading">
                <h4>Deal Setting</h4>
            </div>
            <div class="panel-body">

            <?= $form->field($model, 'deal_duration')->textInput() ?>
            <?= $form->field($model, 'deal_fee')->textInput() ?>
            </div>
        </div>

  
    
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