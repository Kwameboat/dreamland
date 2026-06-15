<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var backend\models\CreatorForm $model */
?>
<div class="creator-form">
    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>

    <?php if ($model->isNewRecord): ?>
        <?= $form->field($model, 'password')->passwordInput() ?>
        <?= $form->field($model, 'confirmPassword')->passwordInput() ?>
    <?php endif; ?>

    <?= $form->field($model, 'status')->dropDownList($model->getStatusDropDownData()) ?>
    <?= $form->field($model, 'is_verified')->dropDownList($model->getVerifiedStatusDropDownData()) ?>
    <?= $form->field($model, 'imageFile')->fileInput() ?>

    <?php if (!$model->isNewRecord && $model->image): ?>
        <p><?= Html::img(Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_USER, $model->image), ['alt' => 'Avatar', 'width' => '64', 'height' => '64', 'style' => 'border-radius:8px;']) ?></p>
    <?php endif; ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create creator' : 'Save changes', ['class' => 'btn btn-success']) ?>
        <?= Html::a('Cancel', $model->isNewRecord ? ['index'] : ['view', 'id' => $model->id], ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
