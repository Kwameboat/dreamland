<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\data\ArrayDataProvider;
use kartik\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'View Audience Details';
$this->params['breadcrumbs'][] = ['label' => 'Driver Document', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
//\yii\web\YiiAsset::register($this);
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box">

            <div class="box-body">
                <p>

                
                    <?=
                    // print_r(@$model->document);
                    DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            
                            [
                                'attribute' => 'name',
                                'value' => function ($model) {
                                    return $model->user->name;
                                }
                            ],
                            [
                                'attribute' => 'email',
                                'value' => function ($model) {
                                    return $model->user->email;
                                }
                            ],
                            'gender',
                            'age_start_range',
                            'age_end_range',
                            'location_type',
                            'radius',
                            'latitude',
                            'longitude',
                            [
                                'attribute' => 'profile_category_type',
                                'label' => 'Profile category',
                                'value' => function ($model) {
                                   
                                    return @$model->profileCategory->name;
                                }
                            ],
                            [
                                'attribute' => 'audience_id',
                                'label' => 'Promotion Interest',
                                'value' => function ($model) {
                                    $intrest ='';
                                    foreach($model->promotionInterest as $data){
                                        $intrest .=  $data->interest .', ' ;
                                    }
                                    $intrest = rtrim($intrest, ', ');
                                    return $intrest;
                                }
                            ],
                            [
                                'attribute' => 'audience_id',
                                'label' => 'Promotion Country',
                                'value' => function ($model) {
                                    $country ='';
                                    foreach($model->promotionCountry as $data){
                                        $country .=  $data->fullname .', ' ;
                                    }
                                    $country = rtrim($country, ', ');
                                    return $country;
                                }
                            ],
                            [
                                'attribute' => 'audience_id',
                                'label' => 'Promotion State',
                                'value' => function ($model) {
                                    $state ='';
                                    foreach($model->promotionState as $data){
                                        $state .=  $data->fullname .', ' ;
                                    }
                                    $state = rtrim($state, ', ');
                                    return $state;
                                }
                            ],
                            [
                                'attribute' => 'audience_id',
                                'label' => 'Promotion City',
                                'value' => function ($model) {
                                    $city ='';
                                    foreach($model->promotionCity as $data){
                                        $city .=  $data->fullname .', ' ;
                                    }
                                    $city = rtrim($city, ', ');
                                    return $city;
                                }
                            ],
                            'created_at:datetime',
                            
                            
                           
                            // [
                            //     'attribute' => 'status',
                            //     'format' => 'html',
                            //     'value' => function ($model) {
                            //         return Html::img(@$model->dlImageUrl  , ['width' => '70px', 'height' => '60px']);
                            //     }
                            // ],

                            
                           
                        ],
                    ]) ?>
                </p>
            </div>


        </div>

    </div>
</div>