<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\data\ArrayDataProvider;
use kartik\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'Driver Document Detail  ';
$this->params['breadcrumbs'][] = ['label' => 'Driver Document', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
//\yii\web\YiiAsset::register($this);
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box">

            <div class="box-body">
                <p>

                <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                    <?=
                    // print_r(@$model->document);
                    DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            
                            [
                                'attribute' => 'Name',
                                'value' => function ($model) {
                                    return $model->user->name;
                                }
                            ],
                            [
                                'attribute' => 'Email',
                                'value' => function ($model) {
                                    return $model->user->email;
                                }
                            ],
                            // 'createdAt:datetime',
                            // 'updatedAt:datetime',
                           
                            [
                                'attribute' => 'driving_license',
                                'format' => 'html',
                                'value' => function ($model) {
                                    return Html::img(@$model->dlImageUrl  , ['width' => '70px', 'height' => '60px']);
                                }
                            ],

                            
                            [
                                'attribute' => 'is_dl_approved',
                                'value' => function ($model) {
                                    return $model->is_dl_approved == 1 ? 'Yes' : 'No';
                                }
                            ],
                            [
                                'attribute' => 'registration_certificate',
                                'format' => 'html',
                                'value' => function ($model) {
                                    return Html::img(@$model->rcImageUrl  , ['width' => '70px', 'height' => '60px']);
                                }
                            ],
                            [
                                'attribute' => 'is_rc_approved',
                                'value' => function ($model) {
                                    return $model->is_rc_approved == 1 ? 'Yes' : 'No';
                                }
                            ],
                            [
                                'attribute' => 'vehicle_insurance',
                                'format' => 'html',
                                'value' => function ($model) {
                                    return Html::img(@$model->viImageUrl  , ['width' => '70px', 'height' => '60px']);
                                }
                            ],
                            [
                                'attribute' => 'is_vi_approved',
                                'value' => function ($model) {
                                    return $model->is_vi_approved == 1 ? 'Yes' : 'No';
                                }
                            ],
                            [
                                'attribute' => 'vehicle_permit',
                                'format' => 'html',
                                'value' => function ($model) {
                                    return Html::img(@$model->vpImageUrl  , ['width' => '70px', 'height' => '60px']);
                                }
                            ],
                            [
                                'attribute' => 'is_vp_approved',
                                'value' => function ($model) {
                                    return $model->is_vp_approved == 1 ? 'Yes' : 'No';
                                }
                            ],
                        ],
                    ]) ?>
                </p>
            </div>


        </div>

    </div>
</div>