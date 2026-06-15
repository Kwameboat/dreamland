<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var backend\models\CreatorForm $model */

$this->title = 'Add Content Creator';
$this->params['breadcrumbs'][] = ['label' => 'Content Creators', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">New creator account</h3></div>
            <div class="box-body">
                <?= $this->render('_form', ['model' => $model]) ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="box">
            <div class="box-header with-border"><h3 class="box-title">What gets created</h3></div>
            <div class="box-body">
                <ul>
                    <li>Role: Content creator (agent)</li>
                    <li>Account type: <code>creator</code></li>
                    <li>Can upload reels and go live in the PWA</li>
                    <li>Receives creator-targeted broadcasts</li>
                </ul>
            </div>
        </div>
    </div>
</div>
