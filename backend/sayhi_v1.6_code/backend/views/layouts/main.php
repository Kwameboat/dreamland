<?php
use yii\helpers\Html;
use yii\web\View;

/* @var $this \yii\web\View */
/* @var $content string */

\backend\assets\AppAsset::register($this);
if (Yii::$app->controller->action->id === 'login') { 
/**
 * Do not use this code in your template. Remove it. 
 * Instead, use the code  $this->layout = '//main-login'; in your controller.
 */
    echo $this->render(
        'main-login',
        ['content' => $content]
    );
} else {

   /*if (class_exists('backend\assets\AppAsset')) {
        backend\assets\AppAsset::register($this);
    } else {
        app\assets\AppAsset::register($this);
    }*/
    backend\assets\AdminLteAsset::register($this);
 
    //dmstr\web\AdminLteAsset::register($this);

    $directoryAsset = Yii::$app->assetManager->getPublishedUrl('@vendor/almasaeed2010/adminlte/dist');
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
        <?php $this->registerJsFile('@web/js/dreamland-admin.js', ['depends' => [\backend\assets\AdminLteAsset::class]]); ?>
        <?php $this->head() ?>
    </head>
    <body class="hold-transition skin-black sidebar-mini dreamland-admin">
    <?php $this->beginBody() ?>
    <div class="dl-bg-scene" aria-hidden="true">
        <div class="dl-bg-orb dl-bg-orb--1"></div>
        <div class="dl-bg-orb dl-bg-orb--2"></div>
        <div class="dl-bg-orb dl-bg-orb--3"></div>
        <div class="dl-bg-grid"></div>
    </div>
    <div class="wrapper">

        <?= $this->render(
            'header.php',
            ['directoryAsset' => $directoryAsset]
        ) ?>

        <?= $this->render(
            'left.php',
            ['directoryAsset' => $directoryAsset]
        )
        ?>

        <?= $this->render(
            'content.php',
            ['content' => $content, 'directoryAsset' => $directoryAsset]
        ) ?>

    </div>

    <?php $this->endBody() ?>
    </body>
    </html>
    <?php $this->endPage() ?>
<?php } ?>
