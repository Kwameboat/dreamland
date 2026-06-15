<?php
use backend\assets\AppAsset;
use yii\helpers\Html;
use dmstr\widgets\Alert;

/* @var $this \yii\web\View */
/* @var $content string */

dmstr\web\AdminLteAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->registerCssFile('@web/css/dreamland-admin.css', ['depends' => [\backend\assets\AdminLteAsset::class]]); ?>
    <?php $this->head() ?>
</head>
<body class="login-page dreamland-login">

<?php $this->beginBody() ?>
<div class="dl-bg-scene" aria-hidden="true">
    <div class="dl-bg-orb dl-bg-orb--1"></div>
    <div class="dl-bg-orb dl-bg-orb--2"></div>
    <div class="dl-bg-orb dl-bg-orb--3"></div>
    <div class="dl-bg-grid"></div>
</div>
<?= Alert::widget() ?>
    <?= $content ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
