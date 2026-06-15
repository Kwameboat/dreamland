<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use kartik\datetime\DateTimePicker;






?>

<div class="countryy-form">



    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-xs-6">



            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'category_id')->dropDownList($categoryData, ['prompt' =>'Select']); ?>


                <?php


                echo $form->field($model, 'start_date')->widget(DateTimePicker::classname(), [
                    'options' => ['placeholder' => 'Enter event start time ...'],
                    'pluginOptions' => [
                        'autoclose' => true,
                        
                        'format' => 'yyyy-mm-dd HH:ii P',

                    ]
                ]);

 
                echo $form->field($model, 'end_date')->widget(DateTimePicker::classname(), [
                    'options' => ['placeholder' => 'Enter event end time ...'],
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'yyyy-mm-dd HH:ii P',
                    ]
                ]);


                /*echo $form->field($model, 'end_date')->widget(DatePicker::classname(), [
                    'options' => ['placeholder' => 'Enter end date ...'],
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'yyyy-mm-dd',
                    ],
                ]);*/ ?>


                <?= $form->field($model, 'place_name')->textInput(['maxlength'=> true]) ?>
                <?= $form->field($model, 'latitude')->textInput(['maxlength' => true]) ?>
                <?= $form->field($model, 'longitude')->textInput(['maxlength' => true]) ?>
                <?= $form->field($model, 'address')->textArea(['maxlength' => true, 'rows'=> 3]) ?>
                
                <?= $form->field($model, 'organisor_id')->dropDownList($eventOrganisorData, ['prompt' =>'Select']); ?>
                <?= $form->field($model, 'is_paid')->dropDownList($model->getIsDropDownData(), ['prompt' =>'Select']); ?>

               


                <?= $form->field($model, 'status')->dropDownList($model->getStatusDropDownData()); ?>
                
            <div class="form-group">
                    <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
                </div>

        </div>


        <div class="col-xs-6">


        

        <?= $form->field($model, 'imageFile')->fileInput() ?>
            <?php if (!$model->isNewRecord && $model->image) { 
                   
                ?>

                <p>
                    <?= Html::img($model->imageUrl, ['alt' => 'No Image', 'width' => '50px', 'height' => '50px']); ?>
                </p>
        <?php } ?>

            <?= $form->field($model, 'gallaryFile[]')->fileInput(['multiple' => true]) ?>

            <?php if (!$model->isNewRecord) { ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4>Uploaded Example Files</h4>
                </div>
                <div class="panel-body">
                    <?php
                    if (count($model->gallaryImages) > 0) {
                        foreach ($model->gallaryImages as $photo) {
                        ?>
                        <div style="width:100px; float:left">
                            <?php

                           
                                echo Html::img($photo->imageUrl, ['alt' => 'No Image', 'width' => '60px', 'height' => '60px']);
                           

                            echo $form->field($model, 'deletePhoto[]')->checkBox(['label' => 'Delete', 'data-size' => 'small', 'style' => 'margin-bottom:4px;', 'value' => $photo->id]);
                            ?>
                        </div>
                        <?php


                        }
                    } else {
                        echo 'No Images uploaded';
                    }

                    ?>



                </div>
            </div>
            <?php } ?>

            <?= $form->field($model, 'description')->textArea(['maxlength' => true, 'rows'=> 6]) ?>
                <?= $form->field($model, 'disclaimer')->textArea(['maxlength' => true,'rows'=> 6])   ?>



        </div>
        <?php ActiveForm::end(); ?>

    </div>
    <?php
    $js = <<<JS
  //  alert('a')
        
  $(document).ready(function(){
    function hideShowDiv() {
        var inputValue = $("#competition-award_type"). val();
        
        if(inputValue==2){
            $('#price_text_block').slideUp();
            $('#coin_text_block').slideDown();
        }else{
            $('#price_text_block').slideDown();
            $('#coin_text_block').slideUp();

            
            
        }
    }
    
    $('#competition-award_type').change(function(){
        hideShowDiv();
       
    });
    hideShowDiv();




  $(".add-more").click(function(){ 
      var html = $(".copy").html();
      $(".after-add-more").append(html);
  });


  $("body").on("click",".remove",function(){ 
      $(this).parents().parents(".copy_container").remove();
  });







});
JS;
    $this->registerJs($js, \yii\web\view::POS_READY);
    ?>