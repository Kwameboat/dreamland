<?php

use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\AdministratorSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Admin users & privileges';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row dreamland-admin">
    <div class="col-xs-12"><div class="box">
            <div class="box-body">
                <div class="dl-privileges-index-head">
                    <div>
                        <p class="eyebrow">Team access</p>
                        <p class="muted">Create sub-admins and assign module access with the lock icon.</p>
                    </div>
                    <div><?= Html::a('Add admin user', ['create'], ['class' => 'btn btn-success']) ?></div>
                </div>
                <div style="clear:both"></div>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        'name',
                        'username',
                        'email',
                        [
                            'attribute' => 'status',
                            'value' => function ($data) {
                                return $data->getStatus();
                            },
                        ],
                        [
                            'label' => 'Role',
                            'value' => function ($data) {
                                return (int) $data->role === 1 ? 'Super admin' : 'Sub-admin';
                            },
                        ],
                        'created_at:datetime',
                        'last_active:datetime',
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'header' => 'Actions',
                            'template' => '{update} {delete} {permission}',
                            'urlCreator' => function ($action, $model, $key, $index) {
                                if ($action === 'update') {
                                    return Url::to(['administrator/update', 'id' => $model['id']]);
                                }
                                if ($action === 'delete') {
                                    return Url::to(['administrator/delete', 'id' => $model['id']]);
                                }
                                if ($action === 'permission') {
                                    return Url::to(['administrator/auth-permission', 'uid' => $model['id']]);
                                }
                                return '#';
                            },
                            'buttons' => [
                                'delete' => function ($url, $model, $key) {
                                    if (Yii::$app->user->id != $model->id) {
                                        return Html::a('<span class="glyphicon glyphicon-trash"></span>', $url, [
                                            'title' => 'Delete',
                                            'data' => [
                                                'confirm' => 'Are you sure you want to delete this admin user?',
                                                'method' => 'post',
                                            ],
                                        ]);
                                    }
                                },
                                'update' => function ($url, $model, $key) {
                                    if (Yii::$app->user->id != $model->id) {
                                        return Html::a('<span class="glyphicon glyphicon-pencil"></span>', $url, ['title' => 'Edit']);
                                    }
                                },
                                'permission' => function ($url, $model, $key) {
                                    if (Yii::$app->user->id != $model->id && (int) $model->role !== 1) {
                                        return Html::a('<span class="fa fa-lock"></span> Modules', $url, [
                                            'class' => 'btn btn-xs btn-default',
                                            'title' => 'Module access',
                                        ]);
                                    }
                                },
                            ],
                        ],
                    ],
                    'tableOptions' => [
                        'id' => 'theDatatable',
                        'class' => 'table table-striped table-bordered table-hover',
                    ],
                ]); ?>
            </div>
        </div>
    </div>
</div>
