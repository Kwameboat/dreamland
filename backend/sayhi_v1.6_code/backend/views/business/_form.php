<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use kartik\datetime\DateTimePicker;
/* @var $this yii\web\View test*/
/* @var $model app\models\Countryy */
/* @var $form yii\widgets\ActiveForm */
//echo Yii::$app->urlManagerFrontend->baseUrl;
//echo  Url::base(true);
?>

<div class="countryy-form">

    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
    <?= $form->field($model, 'business_category_id')->dropDownList($categoryData,['prompt' => 'Select']); ?>
    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'phone')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'city')->textInput(['maxlength' => true]) ?>
    <?php // $form->field($model, 'city')->dropDownList($city,['prompt' => 'Select']); ?>
    <?= $form->field($model, 'price_range_from')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'price_range_to')->textInput(['maxlength' => true]) ?>
    <?php /* $form->field($model, 'open_time')->widget(DateTimePicker::classname(), [
        'options' => ['placeholder' => 'Enter Show Time date ...'],
        //'size' => 'lg',
        'pluginOptions' => [
            'autoclose' => true,
            'format' => 'h:i:a',
        ],
    ]); */ ?> 
    <?= $form->field($model, 'open_time')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'close_time')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'address')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'location')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'latitude')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'longitude')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'status')->dropDownList($model->getStatusDropDownData()); ?>
    
    
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

    <?php ActiveForm::end(); ?>

</div>


<?php
$js=<<< JS
  //  alert('a')
        
  $(document).ready(function(){

});
JS;
$this->registerJs($js,\yii\web\view::POS_READY);
?>
