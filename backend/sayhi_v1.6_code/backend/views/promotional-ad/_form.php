<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use kartik\date\DatePicker;







/* @var $this yii\web\View */
/* @var $model app\models\Countryy */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="countryy-form">

    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

    <?= $form->field($model, 'ad_type')->dropDownList($model->getAdTypeDropDownData()); ?>
    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
    <?php 
  
    // Usage with model and Active Form (with no default initial value)
    echo $form->field($model, 'start_date')->widget(DatePicker::classname(), [
        'options' => ['placeholder' => 'Enter start date ...'],
        'pluginOptions' => [
            'autoclose'=>true,
            'format' => 'yyyy-mm-dd'
        ]
    ]);

    echo $form->field($model, 'end_date')->widget(DatePicker::classname(), [
        'options' => ['placeholder' => 'Enter end date ...'],
        'pluginOptions' => [
            'autoclose'=>true,
            'format' => 'yyyy-mm-dd'
        ]
    ]);
    ?>
    
    

    <?php // $form->field($model, 'category_id')->dropDownList($mainCategoryData,['prompt'=>'Select']); ?>

    <?php
    echo $form->field($model, 'category_id')->widget(Select2::classname(), [
        'data' => $mainCategoryData,
        'language' => 'en',
        'theme' => Select2::THEME_DEFAULT,
        'options' => ['multiple' => true, 'placeholder' => 'Select categories'],
        'pluginOptions' => [
            'allowClear' => true,
        ],
    ]);
    ?>
    <?php
    echo $form->field($model, 'country_id')->widget(Select2::classname(), [
        'data' => $countryDataList,
        'language' => 'en',
        'theme' => Select2::THEME_DEFAULT,
        'options' => ['multiple' => false, 'placeholder' => 'Select Country'],
        'pluginOptions' => [
            'allowClear' => true,
        ],
    ]);
    ?>
    <?= $form->field($model, 'status')->dropDownList($model->getStatusDropDownData()); ?>
    <?= $form->field($model, 'imageFile')->fileInput()->hint('Banner size should be 1142*340 for best display') ?>
    <?php if(!$model->isNewRecord && $model->image ){ ?>
    <p><?= Html::img($model->imageUrl, ['alt' => 'No Image', 'width' => '50px', 'height' => '50px']);?>
    </p>
    <?php }?>
    <?= $form->field($model, 'videoFile')->fileInput() ?>

    <?php if(!$model->isNewRecord && $model->video && $model->ad_type==$model::AD_TYPE_VIDEO ){ ?>
    <p>
        <video width="30%" poster="<?=$model->imageUrl?>" controls>
            <source src="<?=$model->videoUrl?>" type="video/mp4">
            <source src="movie.ogg" type="video/ogg">
            Your browser does not support the video tag.
        </video>

    </p>
    <?php }?>
    
    
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
