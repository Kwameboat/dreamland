<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\data\ArrayDataProvider;
use kartik\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'View Promotion Detail';
$this->params['breadcrumbs'][] = ['label' => 'Promotion', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
//\yii\web\YiiAsset::register($this);
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box">

            <div class="box-body">
                <p>

                <?= Html::a('Payment Details', ['payment/promotion-payment', 'promotion_id' => $model->id], ['class' => 'btn btn-primary']) ?>
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
                                'attribute' => 'Username',
                                'attribute' => 'Username',
                                'value' => function ($model) {
                                    return $model->user->username;
                                }
                            ],
                            [
                                'attribute' => 'Email',
                                'value' => function ($model) {
                                    return $model->user->email;
                                }
                            ],
                            'amount',
                            'duration',
                            'total_amount',
                            'total_spend',
                            'tax',
                            'grand_amount',
                            'daily_promotion_limit',
                            'total_reached',
                            'total_uniq_reached',
                            'url_text',
                            'url',
                            'created_at:datetime',
                            'expiry:datetime',
                           
                        ],
                    ]) ?>
                </p>
            </div>


        </div>

    </div>
</div>