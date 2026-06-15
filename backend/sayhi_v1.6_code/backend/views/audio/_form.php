<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="countryy-form">

    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
    <?= $form->field($model, 'category_id')->dropDownList($mainCategoryData,['prompt'=>'Select']); ?>
    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'artist')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'status')->dropDownList($model->getStatusDropDownData()); ?>
    <?= $form->field($model, 'duration')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'audioFile')->fileInput() ?>
    <?php  if(!$model->isNewRecord && $model->audio ){ 
      ?>
    <p>
        <audio controls>
            <source src="horse.ogg" type="audio/ogg">
            <source src="<?=$model->audioUrl?>" type="audio/mpeg">
            Your browser does not support the audio element.
        </audio>
        
    
    </p>
    <?php }  ?>
    <?= $form->field($model, 'imageFile')->fileInput() ?>
    
    <?php  if(!$model->isNewRecord && $model->image ){ 
      ?>
    
    <p><?= Html::img($model->imageUrl, ['alt' => 'No Image', 'width' => '50px', 'height' => '50px']);?>
    </p>
    <?php }  ?>
    
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
