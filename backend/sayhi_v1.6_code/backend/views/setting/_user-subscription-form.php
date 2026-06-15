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
            <h4>Requirement to activate Subscription plan</h4>
        </div>
        <div class="panel-body">
            
            <?= $form->field($model, 'subscribe_active_condition_follower')->textInput(['maxlength' => true])->label('Minimum Followers') ?>
            <?= $form->field($model, 'subscribe_active_condition_post')->textInput(['maxlength' => true])->label('Minimum Posts')  ?>
            
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