<?php
use kartik\grid\GridView;
use yii\helpers\Html;
use app\models\User;
use common\helpers\DreamlandCreatorApproval;
use common\models\DreamlandAudience;

/** @var yii\web\View $this */
/** @var backend\models\CreatorSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var string $filter */

$this->title = 'Content Creators';
$this->params['breadcrumbs'][] = $this->title;

$tabs = [
    'all' => 'All Creators',
    'active' => 'Active',
    'banned' => 'Suspended / Banned',
    'pending' => 'Pending Approval',
];
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">360° Creator Management</h3>
                <div class="box-tools pull-right">
                    <?= Html::a('<i class="fa fa-plus"></i> Add Creator', ['create'], ['class' => 'btn btn-success btn-sm']) ?>
                    <?= Html::a('<i class="fa fa-bullhorn"></i> Broadcast', ['/broadcast-notification/create', 'audience' => DreamlandAudience::CREATORS], ['class' => 'btn btn-default btn-sm']) ?>
                </div>
            </div>
            <div class="box-body">
                <p class="text-muted" style="margin-bottom:12px;">
                    <strong>Pending Approval</strong> lists creators waiting to publish in the PWA.
                    Click <strong>Approve</strong> to set <code>dreamland_creator_status</code> to approved and unlock upload / live.
                </p>
                <ul class="nav nav-pills" style="margin-bottom:16px;">
                    <?php foreach ($tabs as $key => $label): ?>
                        <li class="<?= $filter === $key ? 'active' : '' ?>">
                            <?= Html::a($label, ['index', 'filter' => $key]) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'kartik\grid\SerialColumn'],
                        'name',
                        'username',
                        'email',
                        [
                            'label' => 'PWA approval',
                            'format' => 'raw',
                            'value' => static function ($model) {
                                $status = DreamlandCreatorApproval::resolveStatus($model);
                                $class = $status === DreamlandCreatorApproval::STATUS_APPROVED ? 'label-success'
                                    : ($status === DreamlandCreatorApproval::STATUS_PENDING ? 'label-warning' : 'label-default');
                                return Html::tag('span', Html::encode(DreamlandCreatorApproval::label($status)), ['class' => 'label ' . $class]);
                            },
                        ],
                        [
                            'attribute' => 'reel_count',
                            'label' => 'Reels',
                            'format' => 'integer',
                        ],
                        [
                            'attribute' => 'live_count',
                            'label' => 'Live',
                            'format' => 'integer',
                        ],
                        [
                            'attribute' => 'available_coin',
                            'label' => 'Credits',
                        ],
                        [
                            'attribute' => 'status',
                            'filter' => (new User())->getStatusDropDownData(),
                            'format' => 'raw',
                            'value' => static fn($model) => $model->getStatusButton(),
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{view} {update} {approve} {reject} {ban} {delete}',
                            'buttons' => [
                                'view' => static fn($url, $model) => Html::a('<span class="glyphicon glyphicon-eye-open"></span>', $url, ['title' => 'Manage']),
                                'update' => static fn($url, $model) => Html::a('<span class="glyphicon glyphicon-pencil"></span>', $url, ['title' => 'Edit']),
                                'approve' => static function ($url, $model) {
                                    if (!DreamlandCreatorApproval::isPending($model)) {
                                        return '';
                                    }
                                    return Html::a('<span class="glyphicon glyphicon-ok"></span>', ['approve', 'id' => $model->id], [
                                        'title' => 'Approve creator (unlock PWA)',
                                        'data' => ['method' => 'post', 'confirm' => 'Approve this creator for upload and live in the PWA?'],
                                    ]);
                                },
                                'reject' => static function ($url, $model) {
                                    if (!DreamlandCreatorApproval::isPending($model)) {
                                        return '';
                                    }
                                    return Html::a('<span class="glyphicon glyphicon-remove"></span>', ['reject', 'id' => $model->id], [
                                        'title' => 'Reject application',
                                        'data' => ['method' => 'post', 'confirm' => 'Reject this creator application?'],
                                    ]);
                                },
                                'ban' => static function ($url, $model) {
                                    if ((int) $model->status === User::STATUS_INACTIVE) {
                                        return Html::a('<span class="glyphicon glyphicon-ok-circle"></span>', ['unban', 'id' => $model->id], [
                                            'title' => 'Reactivate',
                                            'data' => ['method' => 'post', 'confirm' => 'Reactivate this creator?'],
                                        ]);
                                    }
                                    return Html::a('<span class="glyphicon glyphicon-ban-circle"></span>', ['ban', 'id' => $model->id], [
                                        'title' => 'Suspend',
                                        'data' => ['method' => 'post', 'confirm' => 'Suspend this creator? They will not be able to sign in.'],
                                    ]);
                                },
                                'delete' => static fn($url, $model) => Html::a('<span class="glyphicon glyphicon-trash"></span>', ['delete', 'id' => $model->id], [
                                    'title' => 'Delete',
                                    'data' => ['method' => 'post', 'confirm' => 'Delete this creator account permanently?'],
                                ]),
                            ],
                        ],
                    ],
                ]) ?>
            </div>
        </div>
    </div>
</div>
