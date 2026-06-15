<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
?>

<div class="countryy-form">
    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
    <div class="col-xs-12">
    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'category_id')->dropDownList($categoryData, ['prompt' =>'Select']); ?>
    <?= $form->field($model, 'target_value')->textInput() ?>
    <?= $form->field($model, 'raised_value')->textInput() ?>

    <!--  -->
    <?php
    echo $form->field($model, 'start_date')->widget(DatePicker::classname(), [
        'options' => ['placeholder' => 'Enter start date ...'],
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



    <?= $form->field($model, 'status')->dropDownList($model->getStatusDropDownData()); ?>
    <?= $form->field($model, 'campaigner_id')->dropDownList($orgnationdata,['prompt' => 'Select']); ?>
    <?= $form->field($model, 'campaign_for_id')->dropDownList($orgnationdata,['prompt' => 'Select']); ?>



    <?= $form->field($model, 'imageFile')->fileInput() ?>
    <?php if(!$model->isNewRecord && $model->cover_image ){ ?>
    
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
            $media_type =$photo->media_type;
             if($media_type == 1){

                echo Html::img($photo->imageUrl, ['alt' => 'No Image', 'width' => '100px', 'height' => '100px']);   
             }else if($media_type==2){
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