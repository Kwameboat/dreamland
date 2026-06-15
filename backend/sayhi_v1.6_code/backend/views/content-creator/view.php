<?php
use kartik\grid\GridView;
use yii\helpers\Html;
use app\models\User;
use common\models\DreamlandAudience;

/** @var app\models\User $model */
/** @var yii\data\ActiveDataProvider $reelsProvider */
/** @var yii\data\ActiveDataProvider $liveProvider */
/** @var array $stats */

$this->title = 'Creator: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Content Creators', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$isBanned = (int) $model->status === User::STATUS_INACTIVE;
$isPending = (int) $model->status === User::STATUS_PENDING;
?>
<div class="row">
    <div class="col-xs-12" style="margin-bottom:12px;">
        <?= Html::a('<i class="fa fa-pencil"></i> Edit', ['update', 'id' => $model->id], ['class' => 'btn btn-primary btn-sm']) ?>
        <?= Html::a('<i class="fa fa-money"></i> Credits', ['update-credits', 'id' => $model->id], ['class' => 'btn btn-default btn-sm']) ?>
        <?= Html::a('<i class="fa fa-bullhorn"></i> Message', ['/broadcast-notification/create', 'audience' => DreamlandAudience::CUSTOM], ['class' => 'btn btn-default btn-sm']) ?>

        <?php if ($isPending): ?>
            <?= Html::beginForm(['approve', 'id' => $model->id], 'post', ['style' => 'display:inline']) ?>
            <?= Html::submitButton('<i class="fa fa-check"></i> Approve', ['class' => 'btn btn-success btn-sm']) ?>
            <?= Html::endForm() ?>
        <?php endif; ?>

        <?php if ($isBanned): ?>
            <?= Html::beginForm(['unban', 'id' => $model->id], 'post', ['style' => 'display:inline']) ?>
            <?= Html::submitButton('<i class="fa fa-unlock"></i> Reactivate', ['class' => 'btn btn-success btn-sm', 'data-confirm' => 'Reactivate this creator?']) ?>
            <?= Html::endForm() ?>
        <?php else: ?>
            <?= Html::beginForm(['ban', 'id' => $model->id], 'post', ['style' => 'display:inline']) ?>
            <?= Html::submitButton('<i class="fa fa-ban"></i> Suspend', ['class' => 'btn btn-warning btn-sm', 'data-confirm' => 'Suspend this creator?']) ?>
            <?= Html::endForm() ?>
        <?php endif; ?>

        <?= Html::beginForm(['demote', 'id' => $model->id], 'post', ['style' => 'display:inline']) ?>
        <?= Html::submitButton('<i class="fa fa-user"></i> Demote to viewer', [
            'class' => 'btn btn-default btn-sm',
            'data-confirm' => 'Move this account to general users (viewer)?',
        ]) ?>
        <?= Html::endForm() ?>

        <?= Html::a('<i class="fa fa-trash"></i> Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger btn-sm',
            'data' => ['method' => 'post', 'confirm' => 'Delete this creator permanently?'],
        ]) ?>
    </div>
</div>

<div class="row">
    <div class="col-md-3 col-sm-6">
        <div class="info-box">
            <span class="info-box-icon bg-aqua"><i class="fa fa-play-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Reels</span>
                <span class="info-box-number"><?= (int) $stats['reels'] ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box">
            <span class="info-box-icon bg-green"><i class="fa fa-video-camera"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Live sessions</span>
                <span class="info-box-number"><?= (int) $stats['live'] ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box">
            <span class="info-box-icon bg-yellow"><i class="fa fa-database"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Credits</span>
                <span class="info-box-number"><?= (int) $stats['credits'] ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box">
            <span class="info-box-icon bg-red"><i class="fa fa-flag"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Status</span>
                <span class="info-box-number" style="font-size:18px;"><?= Html::encode($model->getStatus()) ?></span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Profile</h3></div>
            <div class="box-body">
                <?php if ($model->image): ?>
                    <p><?= Html::img(Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_USER, $model->image), ['alt' => 'Avatar', 'width' => '80', 'style' => 'border-radius:12px;']) ?></p>
                <?php endif; ?>
                <p><strong>Name:</strong> <?= Html::encode($model->name) ?></p>
                <p><strong>Username:</strong> <?= Html::encode($model->username) ?></p>
                <p><strong>Email:</strong> <?= Html::encode($model->email) ?></p>
                <p><strong>Account type:</strong> <?= Html::encode($model->dreamland_account_type ?: 'creator') ?></p>
                <p><strong>Role:</strong> <?= Html::encode($model->getRole()) ?></p>
                <p><strong>Verified:</strong> <?= Html::encode($model->getIsVerifiedString()) ?></p>
                <p><strong>Joined:</strong> <?= Yii::$app->formatter->asDatetime($model->created_at) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="box">
            <div class="box-header with-border"><h3 class="box-title">Reels</h3></div>
            <div class="box-body">
                <?= GridView::widget([
                    'dataProvider' => $reelsProvider,
                    'columns' => [
                        'id',
                        'title',
                        [
                            'attribute' => 'status',
                            'value' => static function ($row) {
                                if ((int) $row->status === 10) return 'Active';
                                if ((int) $row->status === 9) return 'Blocked';
                                return (string) $row->status;
                            },
                        ],
                        'created_at:datetime',
                    ],
                ]) ?>
            </div>
        </div>
        <div class="box">
            <div class="box-header with-border"><h3 class="box-title">Live history</h3></div>
            <div class="box-body">
                <?= GridView::widget([
                    'dataProvider' => $liveProvider,
                    'columns' => [
                        'id',
                        'channel_name',
                        'start_time:datetime',
                        'end_time:datetime',
                        'total_time',
                    ],
                ]) ?>
            </div>
        </div>
    </div>
</div>
