<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'Update user coins';
$this->params['breadcrumbs'][] = ['label' => 'User', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>


<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <!-- <div class="box-header">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>

            </div>
             -->
            <div class="box-body">

        <?= DetailView::widget([
            'model' => $model,
            'attributes' => [
                'username',
                'email',
                // 'available_balance',
                'available_coin',

            ],
        ]) ?>

    <!-------------- assign package form start here ------------------>
    <div class="countryy-form">

    <?php $form = ActiveForm::begin(); ?>

    
    <?= $form->field($model, 'update_coin')->textInput()->label(false)->hint('(Use minus (-) to detuct coin'); ?>
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    </div>
    <!--------------  assign package form end here ------------------->
</div>



</div>

</div>
</div>
