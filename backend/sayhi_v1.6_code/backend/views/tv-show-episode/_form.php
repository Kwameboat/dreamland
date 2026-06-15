<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use kartik\date\DatePicker;
use kartik\datetime\DateTimePicker;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */
/* @var $form yii\widgets\ActiveForm */
//echo Yii::$app->urlManagerFrontend->baseUrl;
//echo  Url::base(true);
?>

<div class="countryy-form">
<?php if(isset($_GET['tv_show_id'])){
        $model->tv_show_id = $_GET['tv_show_id'];
  } ?>
    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
    <?php if(isset($_GET['tv_show_id'])){
        echo $form->field($model, 'tv_show_id')->dropDownList($tvShowData,['prompt' => 'Select','class'=>'hidden'])->label(false);
    }else{
       echo $form->field($model, 'tv_show_id')->dropDownList($tvShowData,['prompt' => 'Select']);
    } ?>
    
    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
    
    <?= $form->field($model, 'episode_period')->textInput() ?>
   
   <?= $form->field($model, 'created_at')->widget(DatePicker::classname(), [
        'options' => ['placeholder' => 'Enter Show date ...'],
        //'size' => 'lg',
        'pluginOptions' => [
            'autoclose' => true,
            'format' => 'yyyy-mm-dd',
        ],
    ]); ?>
     <?= $form->field($model, 'status')->dropDownList($model->getStatusDropDownData()); ?>
    <?= $form->field($model, 'imageFile')->fileInput() ?>
    <?php if(!$model->isNewRecord && $model->image ){ ?>
    
    <p><?= Html::img($model->imageUrl, ['alt' => 'No Image', 'width' => '50px', 'height' => '50px']);?>
    </p>
    <?php }?>


    <?= $form->field($model, 'file_source')->dropDownList($model->getFileSourceDropDownData()); ?>
    <div id="manuall_upload">
        <?= $form->field($model, 'videoFile')->fileInput() ?>
    </div>
    <div id="ftp_upload">
        <?= $form->field($model, 'video')->textInput(['maxlength' => true])->label('Video File Name')->hint('Upload file on storage system (StorageContainer/tv-show-episode) and put file name ') ?>
      
    </div>

   
    <?php if(!$model->isNewRecord && $model->video ){
      
        if($model->video){  
     
            
           
                echo '<video width="100" poster="'.$model->imageUrl.'"  height="100" controls>
                <source src="'.$model->videoUrl.'" type="video/mp4">
                <source src="movie.ogg" type="video/ogg"></video>' ; 
           
           

             
        }
        ?>
       <?php ?>
    
    </p>
    <?php }?>

    
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
        var inputValue = $("#tvshowepisode-file_source"). val();
        
        if(inputValue==1){
            $('#ftp_upload').hide();
            $('#manuall_upload').show();
        }else if(inputValue==2){
            $('.hint-block').html("Upload file on storage system (StorageContainer/tv-show-episode) and put file name");
            $('#manuall_upload').hide();
            $('#ftp_upload').show();
        }else{
            
            $('.hint-block').html("Enter the full URL of video");
            $('#manuall_upload').hide();
            $('#ftp_upload').show();
            
        }
    }
    
    $('#tvshowepisode-file_source').change(function(){
        hideShowDiv();
       
    });
 
 
    hideShowDiv();

});
JS;
$this->registerJs($js,\yii\web\view::POS_READY);
?>
