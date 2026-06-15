<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */
/* @var $form yii\widgets\ActiveForm */
?>

</style>
<div class="countryy-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>Social Links</h4>
        </div>
        <div class="panel-body">
            
            <?= $form->field($model, 'facebook')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'youtube')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'twitter')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'linkedin')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'pinterest')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'instagram')->textInput(['maxlength' => true]) ?>
        </div>
    </div>

   
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
<?php
$js = <<<JS
    // alert('a')
   
  

JS;
    $this->registerJs($js, \yii\web\view::POS_READY);
    ?>