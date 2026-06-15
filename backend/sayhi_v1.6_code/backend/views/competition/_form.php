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
   
    
    <?php


    echo $form->field($model, 'start_date')->widget(DatePicker::classname(), [
        'options' => ['placeholder' => 'Enter start date ...'],
        //'size' => 'lg',
        'pluginOptions' => [
            'autoclose' => true,
            'format' => 'yyyy-mm-dd',
        ],
    ]);

    echo $form->field($model, 'end_date')->widget(DatePicker::classname(), [
        'options' => ['placeholder' => 'Enter end date ...'],
        'pluginOptions' => [
            'autoclose' => true,
            'format' => 'yyyy-mm-dd',
        ],
    ]); ?>

    <?php // $form->field($model, 'award_type')->dropDownList($model->getAwardTypeData()); ?>

    

    <!--
    <div id="price_text_block">
        <?= $form->field($model, 'price')->textInput(['maxlength' => true]) ?>
    </div>
    <div id="coin_text_block">
        <?= $form->field($model, 'coin')->textInput(['maxlength' => true]) ?>
    </div>
-->
    <?= $form->field($model, 'joining_fee')->textInput(['maxlength' => true]) ?>
    
    
    <?= $form->field($model, 'status')->dropDownList($model->getStatusDropDownData()); ?>

    <?= $form->field($model, 'competition_media_type')->dropDownList($model->getCompetitionMediaTypeData()); ?>
    
    <?= $form->field($model, 'imageFile')->fileInput() ?>
    <?php if(!$model->isNewRecord && $model->image ){ ?>
    
    <p><?= Html::img($model->imageUrl, ['alt' => 'No Image', 'width' => '50px', 'height' => '50px']);?>
    </p>
    <?php }?>

    <?= $form->field($model, 'exampleFile[]')->fileInput(['multiple' => true]) ?>
    
    <?php if(!$model->isNewRecord ){ ?>
    <div class="panel panel-default">
            <div class="panel-heading">
                <h4>Uploaded Example Files</h4>
            </div>
            <div class="panel-body">
     <?php
     if(count($model->expampleImages)>0){
      
        foreach($model->expampleImages as $photo){
            ?>
            <div style="width:100px; float:left">
            <?php 
            
            if($model->competition_media_type==$model::COMPETITION_MEDIA_TYPE_IMAGE){
                echo Html::img($photo->imageUrl, ['alt' => 'No Image', 'width' => '60px', 'height' => '60px']);   
            }else if($model->competition_media_type==$model::COMPETITION_MEDIA_TYPE_VIDEO){
                //return $photo->imageUrl;
                echo '<video width="100" poster="'.$model->imageUrl.'"  height="100" controls>
                <source src="'.$photo->imageUrl.'" type="video/mp4">
                <source src="movie.ogg" type="video/ogg"></video>' ;
                
            }
            

            echo $form->field($model, 'deletePhoto[]')->checkBox(['label' => 'Delete','data-size'=>'small', 'style'=>'margin-bottom:4px;','value'=>$photo->id]);
            ?>
            </div>
            <?php 


        }
    }else{
        echo 'No Images uploaded';
    }
    
     
     
     ?>   
     
    </div>
    </div>
<?php } ?>   

<?= $form->field($model, 'description')->textArea(['maxlength' => true,'rows'=>6]) ?>
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    </div>

    
    <div class="col-xs-6">

    <?= $form->field($model, 'award_type')->dropDownList($model->getAwardTypeData()); ?>
    <div class="panel panel-default">
        <div class="panel-heading">
           <h4>Competition Position</h4>
            
        </div>
        
        <div class="panel-body">
            
        <div class="after-add-more">
            <?php 
            //print_r($model->competitionPosition);
            
            
            if($model->isNewRecord || count($model->competitionPosition)==0){?>

                <div  class="copy_container">
                    <div class="form-group">
                  

                    <label class="control-label" for="competition-price">Competition Position</label>
                        <input type="text"  class="form-control"   name="competitionPosition[]" required>

                        
                    </div>   
                
                    <div class="form-group">
                    <label class="control-label" for="competition-price">Award Value</label>
                        <input type="text"  class="form-control" name="competitionAward[]" required>

                        
                    </div>   
                
                
                    <div class="input-group-btn"> 

                      
                            <button class="btn btn-success add-more" type="button"><i class="glyphicon glyphicon-plus"></i> Add</button>
                      
                    </div>
                </div>
            <?php

            }else{


            
                    $i=0;
                    foreach($model->competitionPosition as $competitionPosition){ ?>
                        <div  class="copy_container">
                            <div class="form-group">
                            <?php if($i>0){
                            echo '<hr class="featurette-divider">';
                            }?> 

                            <label class="control-label" for="competition-price">Competition Position</label>
                                <input type="text"  class="form-control" value="<?php echo $competitionPosition->title ?>"  name="competitionPosition[]" required>

                                
                            </div>   
                        
                            <div class="form-group">
                            <label class="control-label" for="competition-price">Award Value</label>
                                <input type="text"  class="form-control" value="<?php echo $competitionPosition->award_value ?>" name="competitionAward[]" required>

                                
                            </div>   
                        
                        
                            <div class="input-group-btn"> 

                                <?php if($i==0){?>
                                    <button class="btn btn-success add-more" type="button"><i class="glyphicon glyphicon-plus"></i> Add</button>
                                <?php }else{?>
                                    <button class="btn btn-danger remove" type="button"><i class="glyphicon glyphicon-remove"></i> Remove</button>
                                <?php } ?>
                            </div>
                        </div>


                    <?php
                    $i++;
                    }
             }
            ?>

            <!--
             <div>
                <div class="form-group">
                <label class="control-label" for="competition-price">Competition Position</label>
                    <input type="text"  class="form-control" name="competitionPosition[]">

                    
                </div>   
            
                <div class="form-group">
                <label class="control-label" for="competition-price">Award Value</label>
                    <input type="text"  class="form-control" name="competitionAward[]">

                    
                </div>   
            </div>
            <div class="input-group-btn"> 
                <button class="btn btn-success add-more" type="button"><i class="glyphicon glyphicon-plus"></i> Add</button>
            </div>-->




        </div>
        <div class="copy hide">
            <div class="copy_container">
           
                <div class="form-group">
                <hr class="featurette-divider">
                    <label class="control-label" >Competition Position</label>
                    <input type="text"  class="form-control" name="competitionPosition[]">
                </div>   
                <div class="form-group">
                    <label class="control-label" >Award Value</label>
                    <input type="text"  class="form-control" name="competitionAward[]">
                </div>  
                <div class="input-group-btn"> 
                <button class="btn btn-danger remove" type="button"><i class="glyphicon glyphicon-remove"></i> Remove</button>
                </div> 
            </div>
        </div>
            
          
        </div>
    </div>


   
    </div>
    <?php ActiveForm::end(); ?>

</div>
<?php
$js=<<< JS
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
$this->registerJs($js,\yii\web\view::POS_READY);
?>