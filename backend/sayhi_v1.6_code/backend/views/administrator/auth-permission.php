<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\ModuleAuthUser */
/* @var $moduleList array */
/* @var $admin backend\models\Administrator */

$this->title = 'Module access';
$this->params['breadcrumbs'][] = ['label' => 'Administrators', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$groups = [
    'Administration' => ['administrator'],
    'Users & Creators' => ['user', 'reel', 'post', 'story', 'liveHistory'],
    'Dreamland Operations' => ['dreamlandAppraisal', 'dreamlandModeration', 'dreamlandSafety', 'dreamlandSettings'],
    'Commerce' => ['payment', 'creditPackage', 'package', 'broadcastNotifications', 'promotion', 'coupon'],
    'Community & Support' => ['supportRequest', 'report', 'competition', 'club', 'event', 'dating', 'poll'],
    'Platform Settings' => ['setting'],
    'Legacy / Optional' => ['tvChannel', 'podcast', 'gift', 'faq', 'organization', 'fundRaising', 'job', 'ad'],
];

$byAlias = [];
foreach ($moduleList as $record) {
    $byAlias[$record['alias']] = $record;
}
?>

<div class="dreamland-admin dl-privileges-page">
    <div class="dl-privileges-hero glass-card">
        <div>
            <p class="eyebrow">User privileges</p>
            <h2><?= Html::encode($admin->name ?: $admin->username) ?></h2>
            <p class="muted">@<?= Html::encode($admin->username) ?> · <?= Html::encode($admin->email) ?></p>
        </div>
        <p class="dl-privileges-note">Tick only the modules this admin needs. Unticked modules are hidden and blocked.</p>
    </div>

    <?php $form = ActiveForm::begin(); ?>

    <?php foreach ($groups as $groupTitle => $aliases): ?>
        <?php
        $items = [];
        foreach ($aliases as $alias) {
            if (isset($byAlias[$alias])) {
                $items[] = $byAlias[$alias];
            }
        }
        if ($items === []) {
            continue;
        }
        ?>
        <section class="dl-privileges-group panel panel-default">
            <div class="panel-heading">
                <h4><?= Html::encode($groupTitle) ?></h4>
            </div>
            <div class="panel-body dl-privileges-grid">
                <?php foreach ($items as $record): ?>
                    <div class="dl-privilege-card<?= $record['is_active'] ? ' dl-privilege-card--on' : '' ?>">
                        <?= $form->field($model, 'module_ids[]', [
                            'template' => '{input}',
                            'options' => ['class' => 'dl-privilege-card__check'],
                        ])->checkbox([
                            'label' => false,
                            'value' => $record['id'],
                            'checked' => (bool) $record['is_active'],
                            'class' => 'dl-privilege-checkbox',
                        ]) ?>
                        <span class="dl-privilege-card__title"><?= Html::encode($record['name']) ?></span>
                        <span class="dl-privilege-card__alias"><?= Html::encode($record['alias']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <div class="form-group dl-privileges-actions">
        <?= Html::submitButton('Save module access', ['class' => 'btn btn-success btn-lg']) ?>
        <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<?php
$js = <<<JS
document.querySelectorAll('.dl-privilege-checkbox').forEach(function (input) {
  var sync = function () {
    input.closest('.dl-privilege-card').classList.toggle('dl-privilege-card--on', input.checked);
  };
  input.addEventListener('change', sync);
  sync();
});
JS;
$this->registerJs($js);
?>
