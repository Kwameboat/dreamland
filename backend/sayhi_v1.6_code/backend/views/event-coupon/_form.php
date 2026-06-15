<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;



?>

<div class="countryy-form">



    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-xs-6">



            <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'subtitle')->textInput(['maxlength' => true]) ?>
            


                <?php


                            
                echo $form->field($model, 'expiry_date')->widget(DatePicker::classname(), [
                    'options' => ['placeholder' => 'Enter expiry date ...'],
                    //'size' => 'lg',
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'yyyy-mm-dd',
                    ],
                ]);
             ?>


            
                <?= $form->field($model, 'minimum_order_price')->textInput(['maxlength' => true]) ?>
                <?= $form->field($model, 'coupon_value')->textInput(['maxlength' => true]) ?>
          

               


                <?= $form->field($model, 'status')->dropDownList($model->getStatusDropDownData()); ?>
                
            <div class="form-group">
                    <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
                </div>

        </div>


        <div class="col-xs-6">


        <?= $form->field($model, 'code')->textInput(['maxlength'=> true,'style' => 'text-transform:uppercase']) ?>  

        <?= $form->field($model, 'imageFile')->fileInput() ?>
            <?php if (!$model->isNewRecord && $model->image) { 
                   
                ?>

                <p>
                    <?= Html::img($model->imageUrl, ['alt' => 'No Image', 'width' => '50px', 'height' => '50px']); ?>
                </p>
        <?php } ?>

          

            <?= $form->field($model, 'description')->textArea(['maxlength' => true, 'rows'=> 6]) ?>
          



        </div>
        <?php ActiveForm::end(); ?>

    </div>
   