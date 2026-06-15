<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
?>

<div class="countryy-form">

    <?php $form = ActiveForm::begin(); ?>
    
    
    <?php 
  
    // Usage with model and Active Form (with no default initial value)
    echo $form->field($model, 'featured_exp_date')->widget(DatePicker::classname(), [
        'options' => ['placeholder' => 'Enter expiry date ...'],
        'pluginOptions' => [
            'autoclose'=>true,
            'format' => 'yyyy-mm-dd'
        ]
    ]);
    ?>
 
    
    <?= $form->field($model, 'featured_amount')->textInput(['maxlength' => true]) ?>
    
    
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
