<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use kartik\datetime\DateTimePicker;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */
/* @var $form yii\widgets\ActiveForm */
//echo Yii::$app->urlManagerFrontend->baseUrl;
//echo  Url::base(true);
?>

<div class="countryy-form">
  <?php if(isset($_GET['ques_id'])){
        $model->poll_id = $_GET['ques_id'];
  } ?>
    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
    <?= $form->field($model, 'poll_id')->dropDownList($categoryData,[
        'prompt' => 'Select',
        'options' => [
            $model->poll_id => ['selected' => true],
        ],
        'class' => 'form-control disabled-dropdown', // Apply the disabled CSS class
    ]); ?>
    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'status')->dropDownList($model->getStatusDropDownData()); ?>
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<style>
  .disabled-dropdown {
    appearance: none;
    pointer-events: none;
    background-color: #f0f0f0; /* Change to desired disabled color */
    cursor: not-allowed;
}
</style>
<?php
$js=<<< JS
  //  alert('a')
        
  $(document).ready(function(){
    $('.disabled-dropdown').on('click', function (e) {
        e.preventDefault();
    });
});
JS;
$this->registerJs($js,\yii\web\view::POS_READY);
?>
