<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
?>

<div class="countryy-form">



    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-xs-12">


        

             <div class="form-group">
                <label class="control-label" for="competition-price">Support Request</label><br>
                <?php echo $model->request_message?>
                   
                
            </div>        
                
            <?= $form->field($model, 'reply_message')->textArea(['maxlength' => true,'rows'=>6]) ?>
            
            

            <div class="form-group">
                <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
            </div>

        </div>

            
         
    </div>
    <?php ActiveForm::end(); ?>

</div>