<?php
use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $content string */

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Html::encode($this->title) ?></title>
    <style>
        body { margin: 0; font-family: system-ui, sans-serif; background: #0b1020; color: #f5f7ff; padding: 2rem; }
        .site-error { max-width: 720px; margin: 0 auto; }
        .alert-danger { background: rgba(255, 80, 120, 0.15); border: 1px solid rgba(255, 80, 120, 0.45); padding: 1rem; border-radius: 8px; }
        a { color: #ff5a9a; }
    </style>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<?= $content ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
