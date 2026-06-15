<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="countryy-form">

    <?php $form = ActiveForm::begin(); ?>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>App Theme Setting</h4>
        </div>
        <div class="panel-body">
        <label for="html">Theme color : </label>
        <input type="text" name="Setting[theme_color]" class="theme_color_value" value="<?= $model->theme_color; ?>"> 
        <input type="color" class="theme_color" value="<?= $model->theme_color; ?>"> 
        <br> <br>
        <?= $form->field($model, 'theme_font')->dropDownList($model->getFontDropDownData()); ?>
            
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>Light Theme Setting</h4>
        </div>
        <div class="panel-body">
        <label for="html">Theme light background color : </label> 
        <input type="text" name="Setting[theme_light_background_color]" class="theme_light_background_color_value" value="<?= $model->theme_light_background_color; ?>">
        <input type="color" class="theme_light_background_color" value="<?= $model->theme_light_background_color; ?>">
        <br> <br>
        <label for="html">Theme light text color : </label> 
        <input type="text" name="Setting[theme_light_text_color]" class="theme_light_text_color_value" value="<?= $model->theme_light_text_color; ?>">
         <input type="color" class="theme_light_text_color" value="<?= $model->theme_light_text_color; ?>">
            
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>Dark Theme Setting</h4>
        </div>
        <div class="panel-body">
        <label for="html">Theme dark background color : </label>
        <input type="text" name="Setting[theme_dark_background_color]" class="theme_dark_background_color_value" value="<?= $model->theme_dark_background_color; ?>">
        <input type="color" class="theme_dark_background_color" value="<?= $model->theme_dark_background_color; ?>">
        <br> <br>

        <label for="html">Theme dark text color : </label> 
        <input type="text" name="Setting[theme_dark_text_color]" class="theme_dark_text_color_value"   value="<?= $model->theme_dark_text_color; ?>">
        <input type="color" class="theme_dark_text_color"  value="<?= $model->theme_dark_text_color; ?>">
            
           
        </div>
    </div>
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
<?php
$js = <<<JS

// function  theme_dark_background_color(){
//     alert("fhj")
//     }
$(document).ready(function(){
    $(".theme_dark_background_color").change(function(){
        var theme_dark_background_color = $(this).val();
        $(".theme_dark_background_color_value").val(theme_dark_background_color);
    });

    $(".theme_dark_text_color").change(function(){
        var theme_dark_text_color = $(this).val();
        $(".theme_dark_text_color_value").val(theme_dark_text_color);
    });

    $(".theme_light_text_color").change(function(){
        var theme_light_text_color_color = $(this).val();
        $(".theme_light_text_color_value").val(theme_light_text_color_color);
    });

    $(".theme_light_background_color").change(function(){
        var theme_light_background_color = $(this).val();
        $(".theme_light_background_color_value").val(theme_light_background_color);
    });

    $(".theme_color").change(function(){
        var theme_color = $(this).val();
        $(".theme_color_value").val(theme_color);
    });
    
});
JS;
    $this->registerJs($js, \yii\web\view::POS_READY);
    ?>