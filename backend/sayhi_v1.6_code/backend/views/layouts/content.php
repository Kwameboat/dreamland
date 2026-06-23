<?php
use yii\widgets\Breadcrumbs;
use yii\helpers\Html;
use yii\helpers\Url;
use common\widgets\Alert;

$isDashboard = $this->context->id === 'site' && $this->context->action->id === 'index';
$dashboardRefreshUrl = Url::to(['/site/index', 'refresh' => 1]);

?>
<div class="content-wrapper">
    <section class="content-header<?= $isDashboard ? ' content-header--dashboard' : '' ?>">
        <div class="content-header__row">
            <div class="content-header__title">
        <?php if (isset($this->blocks['content-header'])) { ?>
            <h1><?= $this->blocks['content-header'] ?></h1>
        <?php } else { ?>
            <h1>
                <?php
                if ($this->title !== null) {
                    echo Html::encode($this->title);
                } else {
                    echo \yii\helpers\Inflector::camel2words(
                        \yii\helpers\Inflector::id2camel($this->context->module->id)
                    );
                    echo ($this->context->module->id !== \Yii::$app->id) ? '<small>Module</small>' : '';
                } ?>
            </h1>
        <?php } ?>

        <?=
        Breadcrumbs::widget(
            [
                'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
            ]
        ) ?>
            </div>

        <?php if ($isDashboard): ?>
            <div class="dl-dashboard-toolbar" role="toolbar" aria-label="Dashboard actions">
                <?= Html::a('<i class="fa fa-refresh"></i> Refresh dashboard', $dashboardRefreshUrl, [
                    'class' => 'btn btn-default dl-dashboard-toolbar__btn',
                    'id' => 'dl-dashboard-refresh',
                ]) ?>
                <?= Html::beginForm(['/site/update-system'], 'post', ['class' => 'dl-dashboard-inline-form']) ?>
                    <?= Html::submitButton('<i class="fa fa-cogs"></i> Update system', [
                        'class' => 'btn btn-primary dl-dashboard-toolbar__btn dl-dashboard-toolbar__btn--accent',
                        'id' => 'dl-dashboard-update-system',
                    ]) ?>
                <?= Html::endForm() ?>
            </div>
        <?php endif; ?>
        </div>
    </section>

    <section class="content">
        <?= Alert::widget() ?>
        <?= $content ?>
    </section>
</div>

<footer class="main-footer">
    <div class="pull-right hidden-xs">
        <b>Version</b> 1.6
    </div>
    <strong>Copyright &copy; 2020-2025 <a href="#"><?=Yii::$app->name?></a>.</strong> All rights
    reserved.
</footer>
