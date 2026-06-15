<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */
/* @var $form yii\widgets\ActiveForm */

?>

<div class="countryy-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>Payment Setting</h4>
        </div>
        <div class="panel-body">
            <?= $form->field($model, 'each_view_coin')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'min_widhdraw_price')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'min_coin_redeem')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'per_coin_value')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'stripe_publishable_key')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'stripe_secret_key')->textInput(['maxlength' => true]) ?>
            <!-- newadd -->
            <?= $form->field($model, 'razorpay_api_key')->textInput(['maxlength' => true]) ?>
            <!--
            <?= $form->field($model, 'paypal_merchant_id')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'paypal_public_key')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'paypal_private_key')->textInput(['maxlength' => true]) ?>
            -->
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