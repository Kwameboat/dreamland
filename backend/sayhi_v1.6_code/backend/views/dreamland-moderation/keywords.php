<?php
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var common\models\LocalBlacklistKeyword $model */

$this->title = 'Ghana Moderation Keywords';
?>
<div class="dreamland-admin">
    <h1><?= Html::encode($this->title) ?></h1>
    <p><?= Html::a('&larr; AI Moderation', ['index'], ['class' => 'btn btn-default']) ?></p>

    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Add keyword</h3></div>
        <div class="box-body">
            <?php $form = ActiveForm::begin(['action' => ['add-keyword']]); ?>
            <div class="row">
                <div class="col-md-4"><?= $form->field($model, 'keyword')->textInput(['placeholder' => 'e.g. kwasia, momo thief']) ?></div>
                <div class="col-md-2"><?= $form->field($model, 'locale')->dropDownList([
                    'gh' => 'Ghana (general)',
                    'tw' => 'Twi / Akan',
                    'ga' => 'Ga',
                    'ee' => 'Ewe',
                    'ha' => 'Hausa',
                    'dag' => 'Dagbani',
                    'pidgin' => 'Pidgin',
                    'en-gh' => 'English (GH)',
                ]) ?></div>
                <div class="col-md-2"><?= $form->field($model, 'severity')->dropDownList([1 => 'Low', 2 => 'Medium', 3 => 'High']) ?></div>
                <div class="col-md-2"><?= $form->field($model, 'is_active')->dropDownList([1 => 'Active', 0 => 'Inactive']) ?></div>
                <div class="col-md-2"><label>&nbsp;</label><div><?= Html::submitButton('Add', ['class' => 'btn btn-primary btn-block']) ?></div></div>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'id',
            'keyword',
            'locale',
            'severity',
            'is_active:boolean',
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{delete}',
                'buttons' => [
                    'delete' => function ($url, $model) {
                        return Html::beginForm(['delete-keyword', 'id' => $model->id], 'post')
                            . Html::submitButton('Delete', [
                                'class' => 'btn btn-danger btn-xs',
                                'data' => ['confirm' => 'Remove this keyword?'],
                            ])
                            . Html::endForm();
                    },
                ],
            ],
        ],
    ]) ?>
</div>
