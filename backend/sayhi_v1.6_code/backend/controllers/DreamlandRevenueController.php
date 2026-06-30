<?php

namespace backend\controllers;

use common\models\CreditPackageTransaction;
use common\models\PurchasedLive;
use common\models\PurchasedVideo;
use common\models\WithdrawalPayment;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;

class DreamlandRevenueController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [[
                    'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::PAYMENT),
                    'roles' => ['@'],
                ]],
            ],
        ];
    }

    public function actionIndex()
    {
        $topups = (float) CreditPackageTransaction::find()
            ->where(['status' => CreditPackageTransaction::STATUS_COMPLETED])
            ->sum('amount');

        $videoCommission = (int) PurchasedVideo::find()->sum('platform_commission');
        $liveCommission = (int) PurchasedLive::find()->sum('platform_commission');
        $videoCreator = (int) PurchasedVideo::find()->sum('creator_credits');
        $liveCreator = (int) PurchasedLive::find()->sum('creator_credits');

        $pendingWithdrawals = WithdrawalPayment::find()
            ->where(['status' => WithdrawalPayment::STATUS_PENDING]);
        $pendingCount = (int) $pendingWithdrawals->count();
        $pendingAmount = (float) $pendingWithdrawals->sum('amount');

        $completedWithdrawals = (float) WithdrawalPayment::find()
            ->where(['status' => WithdrawalPayment::STATUS_ACCEPTED])
            ->sum('amount');

        $recentTopups = CreditPackageTransaction::find()
            ->where(['status' => CreditPackageTransaction::STATUS_COMPLETED])
            ->orderBy(['completed_at' => SORT_DESC, 'id' => SORT_DESC])
            ->limit(12)
            ->all();

        $recentUnlocks = PurchasedVideo::find()
            ->orderBy(['purchased_at' => SORT_DESC, 'id' => SORT_DESC])
            ->limit(12)
            ->all();

        return $this->render('index', [
            'topups' => $topups,
            'platformCommission' => $videoCommission + $liveCommission,
            'creatorEarnings' => $videoCreator + $liveCreator,
            'pendingCount' => $pendingCount,
            'pendingAmount' => $pendingAmount,
            'completedWithdrawals' => $completedWithdrawals,
            'recentTopups' => $recentTopups,
            'recentUnlocks' => $recentUnlocks,
        ]);
    }
}
