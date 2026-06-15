<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'User Detail : ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'User', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<style>
   
    </style>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <!-- <div class="box-header">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>

            </div>
             -->
            <div class="box-body ">



                <p class="buttonTopPadding">
                    <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>

                    <?= Html::a('Delete', ['delete', 'id' => $model->id], [
                        'class' => 'btn btn-danger',
                        'data' => [
                            'confirm' => 'Are you sure you want to delete this item?',
                            'method' => 'post',
                        ],
                    ]) ?>
                    <?= Html::a('Update Coin', ['update-coin', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                    <?php
                    $totalFollowers = $model->getTotalFollowers($model->id);
                    $totalFollowing = $model->getTotalFollowing($model->id);
                    $totalBlockedUsers = $model->getTotalBlockedUsers($model->id);
                    $totalgetTotalStory = $model->getTotalStory($model->id)
                        ?>
                    <?= Html::a('View all post', ['post/index', 'PostSearch[user_id]' => $model->id], ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('View following (' . $totalFollowing . ')', ['user/following', 'user_id' => $model->id], ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('View followers (' . $totalFollowers . ')', ['user/follower', 'user_id' => $model->id], ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('Blocked users (' . $totalBlockedUsers . ')', ['user/blocked-user-list', 'user_id' => $model->id], ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('View Stories (' . $totalgetTotalStory . ')', ['story/', 'StorySearch[user_id]' => $model->id], ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('User Live', ['user-live-history/', 'UserLiveHistorySearch[user_id]' => $model->id], ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('User Reels', ['audio/post-reels', 'PostSearch[user_id]' => $model->id], ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('Manage User app feature', ['feature-list', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                </p>
                <?php
                if ($model->status == $model::STATUS_PENDING) { ?>

                    <?= Html::a('Approve', ['approve', 'id' => $model->id], [
                        'class' => 'btn btn-success',
                        'data' => [
                            'confirm' => 'Are you sure you want to approve?',
                            'method' => 'post',
                        ],
                    ]) ?>
                    <?= Html::a('Reject', ['reject', 'id' => $model->id], [
                        'class' => 'btn btn-danger',
                        'data' => [
                            'confirm' => 'Are you sure you want to rejct?',
                            'method' => 'post',
                        ],
                    ]) ?>
                    This User is awaiting for admin approval.
                    <?php
                }

                ?>

                <?= DetailView::widget([
                    'model' => $model,
                    'template' => "<tr><th style='width: 30%;'>{label}</th><td>{value}</td></tr>",
                    'attributes' => [

                        'name',
                        'username',

                        [
                            'attribute' => 'email',
                            'value' => function ($data) {
                                return $data->getEmail();
                            },
                        ],
                        [
                            'attribute' => 'role',
                            'value' => function ($data) {
                                return $data->getRole();
                            },
                        ],
                        [
                            'attribute' => 'status',
                            'value' => function ($data) {
                                return $data->getStatusButton();
                            },
                            'format' => 'raw',
                        ],

                        [
                            'attribute' => 'is_verified',
                            'value' => function ($data) {

                                return $data->verifiedStatus;
                            },
                        ],

                        [
                            'attribute' => 'country',
                            'value' => function ($data) {


                                return $data->country;
                            },
                        ],
                        [
                            'attribute' => 'phone',
                            'value' => function ($data) {
                                return $data->getPhone();
                            },
                        ],
                        [
                            'attribute' => 'is_phone_verified',
                            'value' => function ($data) {
                                return $data->phoneVerifiedStatus;
                            },
                        ],
                        [
                            'attribute' => 'is_email_verified',
                            'value' => function ($data) {
                                return $data->emailVerifiedStatus;
                            },
                        ],

                        'bio',
                        'description',
                        'available_balance',
                        'available_coin',
                        'last_active:datetime',
                        'created_at:datetime',
                        'updated_at:datetime',
                        [
                            'attribute' => 'image',
                            'value' => function ($data) {

                                return Html::img($data->imageUrl, ['alt' => 'No Image', 'width' => '50px', 'height' => '50px']);
                                // return Html::img(Yii::$app->urlManagerFrontend->baseUrl.'/uploads/promotional-banner/thumb/'.$model->image, ['alt' => 'No Image', 'width' => '50px', 'height' => '50px']);
                            },
                            'format' => 'raw',
                        ]

                    ],
                ]) ?>

               

            </div>
            <div class="box-header">
                <h3 class="box-title">User last login detail</h3>

            </div>


            <div class="box-body">
                <?php 
                $resultLastLogin = $model->getLastLoginLog();
              
               if($resultLastLogin){
                 echo DetailView::widget([
                    'model' => $resultLastLogin,
                    'template' => "<tr><th style='width: 30%;'>{label}</th><td>{value}</td></tr>",
                    'attributes' => [
                       
                        [
                            'attribute' => 'login_mode',
                            'value' => function ($data) {
                                return $data->getLoginModeString();
                            },
                        ],
                        [
                            'attribute' => 'device_type',
                            'value' => function ($data) {
                                return $data->getDeviceTypeString();
                            },
                        ],
                        'device_model',


                        'created_at:datetime',
                        'login_ip'
                

                    ],
                ]) ;
                }else{
                    echo 'No detail available';
                }
                ?>

            </div>

        </div>

    </div>
</div>