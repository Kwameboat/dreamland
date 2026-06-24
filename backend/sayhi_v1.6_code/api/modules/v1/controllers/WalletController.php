<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\User;
use common\models\GroupWatchPot;
use common\models\PurchasedLive;
use common\models\PurchasedVideo;
use api\modules\v1\models\Payment;
use common\models\CreditPackage;
use common\models\CreditPackageTransaction;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\ActiveController;

class WalletController extends ActiveController
{
    public $modelClass = 'common\models\CreditPackage';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['update'], $actions['index'], $actions['delete'], $actions['view']);
        return $actions;
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),
            'except' => ['packages', 'paystack-webhook'],
            'authMethods' => [HttpBearerAuth::className()],
        ];
        return $behaviors;
    }

    /**
     * GET /api/wallet/packages
     */
    public function actionPackages()
    {
        $keys = Yii::$app->dreamlandWallet->getPaystackKeys();
        $packages = CreditPackage::getActivePackages();
        return [
            'message' => 'ok',
            'paystack_public_key' => $keys['public'],
            'packages' => array_map(static function (CreditPackage $pkg) {
                return [
                    'id' => $pkg->id,
                    'credit_amount' => (int) $pkg->credit_amount,
                    'fiat_cost' => (float) $pkg->fiat_cost,
                    'currency' => $pkg->currency,
                    'label' => $pkg->credit_amount . ' Credits — ' . $pkg->currency . ' ' . number_format((float) $pkg->fiat_cost, 2),
                ];
            }, $packages),
        ];
    }

    /**
     * POST /api/wallet/paystack/initialize
     */
    public function actionPaystackInitialize()
    {
        $body = Yii::$app->request->getBodyParams();
        $packageId = (string) ($body['package_id'] ?? '');
        $userId = Yii::$app->user->identity->id;
        $user = User::findOne($userId);
        $email = (string) ($body['email'] ?? $user->email ?? 'user@dreamland.app');

        if (!$packageId) {
            return ['statusCode' => 422, 'message' => 'package_id is required.'];
        }

        $result = Yii::$app->dreamlandWallet->initializeCheckout($userId, $packageId, $email);
        if (!$result['ok']) {
            return ['statusCode' => 422, 'message' => $result['error']];
        }
        return ['message' => 'Checkout initialized.', 'data' => $result];
    }

    /**
     * POST /v1/wallet/dev-topup — localhost only, no Paystack required
     */
    public function actionDevTopup()
    {
        if (!(Yii::$app->params['dreamlandDevMode'] ?? false)) {
            return ['statusCode' => 403, 'message' => 'Dev top-up is disabled on this server.'];
        }

        $body = Yii::$app->request->getBodyParams();
        $packageId = (string) ($body['package_id'] ?? '');
        if (!$packageId) {
            return ['statusCode' => 422, 'message' => 'package_id is required.'];
        }

        $userId = (int) Yii::$app->user->identity->id;
        $result = Yii::$app->dreamlandWallet->devGrantCredits($userId, $packageId);
        if (!$result['ok']) {
            return ['statusCode' => 422, 'message' => $result['error']];
        }

        return [
            'message' => 'Demo credits added to your wallet (local dev mode).',
            'data' => $result,
        ];
    }

    /**
     * GET /api/wallet/paystack/verify?reference=
     */
    public function actionPaystackVerify()
    {
        $reference = (string) Yii::$app->request->get('reference', '');
        if (!$reference) {
            return ['statusCode' => 422, 'message' => 'reference is required.'];
        }

        $userId = (int) Yii::$app->user->identity->id;
        $txRecord = CreditPackageTransaction::findOne(['paystack_reference' => $reference]);
        if (!$txRecord) {
            return ['statusCode' => 422, 'message' => 'Transaction not found.'];
        }
        if ((int) $txRecord->user_id !== $userId) {
            return ['statusCode' => 403, 'message' => 'This payment belongs to another account.'];
        }

        $result = Yii::$app->dreamlandWallet->verifyAndFulfill($reference);
        if (!$result['ok']) {
            return ['statusCode' => 422, 'message' => $result['error']];
        }
        return ['message' => 'Payment verified.', 'data' => $result];
    }

    /**
     * POST /api/wallet/paystack/webhook
     */
    public function actionPaystackWebhook()
    {
        $payload = json_decode(Yii::$app->request->rawBody, true) ?: [];
        $keys = Yii::$app->dreamlandWallet->getPaystackKeys();
        $signature = (string) Yii::$app->request->headers->get('X-Paystack-Signature', '');
        if (!empty($keys['secret'])) {
            if ($signature === '') {
                Yii::$app->response->statusCode = 401;
                return ['statusCode' => 401, 'message' => 'Missing Paystack signature.'];
            }
            $computed = hash_hmac('sha512', Yii::$app->request->rawBody, $keys['secret']);
            if (!hash_equals($computed, $signature)) {
                Yii::$app->response->statusCode = 401;
                return ['statusCode' => 401, 'message' => 'Invalid signature.'];
            }
        }
        $result = Yii::$app->dreamlandWallet->handleWebhook($payload);
        if (empty($result['ok'])) {
            Yii::warning('Paystack webhook fulfillment failed: ' . ($result['error'] ?? 'unknown'), __METHOD__);
        }
        return ['message' => 'ok', 'data' => $result];
    }

    /**
     * GET /v1/wallet/transactions
     */
    public function actionTransactions()
    {
        $userId = (int) Yii::$app->user->identity->id;
        $items = [];

        $videoPurchases = PurchasedVideo::find()
            ->where(['user_id' => $userId])
            ->orderBy(['id' => SORT_DESC])
            ->limit(50)
            ->all();
        foreach ($videoPurchases as $row) {
            $items[] = [
                'type' => 'video_unlock',
                'reference_id' => (int) $row->video_id,
                'credits' => -(int) $row->credits_paid,
                'at' => $row->purchased_at ?? null,
            ];
        }

        if (Yii::$app->db->schema->getTableSchema('purchased_lives', true)) {
            $livePurchases = PurchasedLive::find()
                ->where(['user_id' => $userId])
                ->orderBy(['id' => SORT_DESC])
                ->limit(50)
                ->all();
            foreach ($livePurchases as $row) {
                $items[] = [
                    'type' => 'live_unlock',
                    'reference_id' => (int) $row->live_id,
                    'credits' => -(int) $row->credits_paid,
                    'at' => $row->purchased_at ?? null,
                ];
            }
        }

        $payments = Payment::find()
            ->where(['user_id' => $userId, 'type' => Payment::TYPE_COIN])
            ->orderBy(['id' => SORT_DESC])
            ->limit(50)
            ->all();
        foreach ($payments as $payment) {
            $sign = (int) $payment->transaction_type === Payment::TRANSACTION_TYPE_CREDIT ? 1 : -1;
            $items[] = [
                'type' => $payment->remarks ?: 'wallet',
                'reference_id' => (int) $payment->detail_reference_id,
                'credits' => $sign * (int) $payment->coin,
                'at' => date('Y-m-d H:i:s', (int) $payment->created_at),
            ];
        }

        usort($items, static function ($a, $b) {
            return strcmp((string) ($b['at'] ?? ''), (string) ($a['at'] ?? ''));
        });

        return ['message' => 'ok', 'transactions' => array_slice($items, 0, 50)];
    }
}
