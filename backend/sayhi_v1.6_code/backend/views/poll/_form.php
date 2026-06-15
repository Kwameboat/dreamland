<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use kartik\datetime\DateTimePicker;
use kartik\date\DatePicker;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */
/* @var $form yii\widgets\ActiveForm */
//echo Yii::$app->urlManagerFrontend->baseUrl;
//echo  Url::base(true);
// print_r($modelPollQuesOption);
// exit('tyui');
?>

<div class="countryy-form">
<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
<div class="row">
    <div class="col-xs-6">
   
    <?= $form->field($model, 'category_id')->dropDownList($categoryData,['prompt' => 'Select']); ?>
    <?php // $form->field($model, 'campaigner_id')->dropDownList($organizationData ,['prompt' => 'Select']); ?>
    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'status')->dropDownList($model->getStatusDropDownData()); ?>
    <?= $form->field($model, 'start_time')->widget(DatePicker::classname(), [
        'options' => ['placeholder' => 'Enter Start Time date ...'],
        //'size' => 'lg',
        'pluginOptions' => [
            'autoclose' => true,
            'format' => 'yyyy-mm-dd',
        ],
    ]); ?>
    <?= $form->field($model, 'end_time')->widget(DatePicker::classname(), [
        'options' => ['placeholder' => 'Enter End Time date ...'],
        //'size' => 'lg',
        'pluginOptions' => [
            'autoclose' => true,
            'format' => 'yyyy-mm-dd',
        ],
    ]); ?>

    <?= $form->field($model, 'description')->textArea(['maxlength' => true,'rows'=>6]) ?>
    
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>
</div>
<?php if(empty($_GET['id'])){
    ?>


    <div class="col-xs-6">
    <div class="panel panel-default">
        <div class="panel-heading">
           <h4>Poll Options</h4>    
        </div>
        <div class="panel-body">
            
        <div class="after-add-more">
        
            <div  class="copy_container">
                <div class="form-group">
                
                <label class="control-label" >Poll Option</label>
                    <input type="text"  class="form-control" name="pollOption[]" required>
                </div>                       
                <div class="input-group-btn">                    
                        <button class="btn btn-success add-more" type="button"><i class="glyphicon glyphicon-plus"></i> Add</button>               
                </div>
            </div>

        </div>
        <div class="copy hide">
            <div class="copy_container">
           
                <div class="form-group">
                <hr class="featurette-divider">
                <label class="control-label" >Poll Option</label>
                    <input type="text" class="form-control" name="pollOption[]">
                </div>   
  
                <div class="input-group-btn"> 
                <button class="btn btn-danger remove" type="button"><i class="glyphicon glyphicon-remove"></i> Remove</button>
                </div> 
            </div>
        </div>
    </div>
    </div>
</div>
<?php } ?>
</div>
<?php




ActiveForm::end(); ?>
</div>
<?php
$js=<<< JS
  //  alert('a')
        
  $(document).ready(function(){
    $(".add-more").click(function(){ 
      var html = $(".copy").html();
      $(".after-add-more").append(html);
  });


  $("body").on("click",".remove",function(){ 
      $(this).parents().parents(".copy_container").remove();
  });

});
JS;
$this->registerJs($js,\yii\web\view::POS_READY);
?>
