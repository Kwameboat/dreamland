<?php

namespace common\components;

use api\modules\v1\models\Payment;
use api\modules\v1\models\User;
use common\models\CreditPackage;
use common\models\CreditPackageTransaction;
use common\models\DreamlandSetting;
use Yii;
use yii\base\Component;
use yii\db\Transaction;

class DreamlandWalletService extends Component
{
    public function getPaystackKeys()
    {
        $public = getenv('PAYSTACK_PUBLIC_KEY') ?: '';
        $secret = getenv('PAYSTACK_SECRET_KEY') ?: '';
        if ($public !== '' && $secret !== '') {
            return ['public' => $public, 'secret' => $secret];
        }

        try {
            $settings = DreamlandSetting::getSettings();
            $public = $public ?: (string) ($settings->getAttribute('paystack_public_key') ?? '');
            $secret = $secret ?: (string) ($settings->getAttribute('paystack_secret_key') ?? '');
        } catch (\Throwable $e) {
            Yii::warning($e->getMessage(), __METHOD__);
        }

        return ['public' => $public, 'secret' => $secret];
    }

    public function initializeCheckout($userId, $packageId, $email)
    {
        $package = CreditPackage::findOne(['id' => $packageId, 'is_active' => 1]);
        if (!$package) {
            return ['ok' => false, 'error' => 'Credit package not found.'];
        }

        $keys = $this->getPaystackKeys();
        if (empty($keys['secret'])) {
            return ['ok' => false, 'error' => 'Paystack is not configured.'];
        }

        $reference = 'DL_' . time() . '_' . $userId . '_' . substr(md5(uniqid('', true)), 0, 8);
        $amountKobo = (int) round(((float) $package->fiat_cost) * 100);

        $tx = new CreditPackageTransaction([
            'user_id' => (int) $userId,
            'credit_package_id' => $package->id,
            'paystack_reference' => $reference,
            'amount' => $package->fiat_cost,
            'currency' => $package->currency,
            'credits_to_grant' => (int) $package->credit_amount,
            'status' => CreditPackageTransaction::STATUS_PENDING,
        ]);
        $tx->save(false);

        $payload = [
            'email' => $email,
            'amount' => $amountKobo,
            'currency' => $package->currency,
            'reference' => $reference,
            'callback_url' => Yii::$app->params['dreamlandPaystackCallbackUrl'] ?? null,
            'metadata' => [
                'user_id' => (int) $userId,
                'credit_package_id' => $package->id,
                'credits' => (int) $package->credit_amount,
            ],
        ];

        $response = $this->paystackRequest('POST', 'transaction/initialize', $payload, $keys['secret']);
        if (empty($response['status']) || empty($response['data']['authorization_url'])) {
            $tx->status = CreditPackageTransaction::STATUS_FAILED;
            $tx->save(false);
            return ['ok' => false, 'error' => $response['message'] ?? 'Paystack initialization failed.'];
        }

        return [
            'ok' => true,
            'authorization_url' => $response['data']['authorization_url'],
            'access_code' => $response['data']['access_code'],
            'reference' => $reference,
            'public_key' => $keys['public'],
            'package' => [
                'id' => $package->id,
                'credit_amount' => (int) $package->credit_amount,
                'fiat_cost' => (float) $package->fiat_cost,
                'currency' => $package->currency,
            ],
        ];
    }

    public function verifyAndFulfill($reference)
    {
        $txRecord = CreditPackageTransaction::findOne(['paystack_reference' => $reference]);
        if (!$txRecord) {
            return ['ok' => false, 'error' => 'Transaction not found.'];
        }
        if ($txRecord->status === CreditPackageTransaction::STATUS_COMPLETED) {
            return ['ok' => true, 'already_completed' => true];
        }

        $keys = $this->getPaystackKeys();
        $response = $this->paystackRequest('GET', 'transaction/verify/' . rawurlencode($reference), null, $keys['secret']);
        if (empty($response['status']) || ($response['data']['status'] ?? '') !== 'success') {
            return ['ok' => false, 'error' => 'Payment not successful.'];
        }

        /** @var Transaction $dbTx */
        $dbTx = Yii::$app->db->beginTransaction();
        try {
            $user = User::findOne($txRecord->user_id);
            if (!$user) {
                throw new \RuntimeException('User not found.');
            }

            $user->available_coin += (int) $txRecord->credits_to_grant;
            $user->save(false);

            $payment = new Payment();
            $payment->type = Payment::TYPE_COIN;
            $payment->user_id = (int) $user->id;
            $payment->coin = (int) $txRecord->credits_to_grant;
            $payment->transaction_type = Payment::TRANSACTION_TYPE_CREDIT;
            $payment->payment_type = Payment::PAYMENT_TYPE_PACKAGE;
            $payment->transaction_id = $reference;
            $payment->payment_mode = Payment::PAYMENT_MODE_FLUTTERWAVE; // reuse slot; Paystack=10 if added later
            $payment->remarks = 'dreamland_credit_package:' . $txRecord->credit_package_id;
            $payment->save(false);

            $txRecord->status = CreditPackageTransaction::STATUS_COMPLETED;
            $txRecord->completed_at = date('Y-m-d H:i:s');
            $txRecord->save(false);

            $dbTx->commit();
            return [
                'ok' => true,
                'credits_granted' => (int) $txRecord->credits_to_grant,
                'reference' => $reference,
            ];
        } catch (\Throwable $e) {
            $dbTx->rollBack();
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function handleWebhook(array $payload)
    {
        $event = $payload['event'] ?? '';
        if ($event !== 'charge.success') {
            return ['ok' => true, 'ignored' => true];
        }
        $reference = $payload['data']['reference'] ?? null;
        if (!$reference) {
            return ['ok' => false, 'error' => 'Missing reference.'];
        }
        return $this->verifyAndFulfill($reference);
    }

    /**
     * Local dev only — grant credits without Paystack (localhost walkthrough).
     */
    public function devGrantCredits(int $userId, string $packageId): array
    {
        if (!($Yii::$app->params['dreamlandDevMode'] ?? false)) {
            return ['ok' => false, 'error' => 'Dev wallet top-up is disabled.'];
        }

        $package = CreditPackage::findOne(['id' => $packageId, 'is_active' => 1]);
        if (!$package) {
            return ['ok' => false, 'error' => 'Credit package not found.'];
        }

        $reference = 'DEV_' . time() . '_' . $userId;
        /** @var Transaction $dbTx */
        $dbTx = Yii::$app->db->beginTransaction();
        try {
            $user = User::findOne($userId);
            if (!$user) {
                throw new \RuntimeException('User not found.');
            }

            $user->available_coin = (int) $user->available_coin + (int) $package->credit_amount;
            $user->save(false);

            $tx = new CreditPackageTransaction([
                'user_id' => $userId,
                'credit_package_id' => $package->id,
                'paystack_reference' => $reference,
                'amount' => $package->fiat_cost,
                'currency' => $package->currency,
                'credits_to_grant' => (int) $package->credit_amount,
                'status' => CreditPackageTransaction::STATUS_COMPLETED,
                'completed_at' => date('Y-m-d H:i:s'),
            ]);
            $tx->save(false);

            $payment = new Payment();
            $payment->type = Payment::TYPE_COIN;
            $payment->user_id = $userId;
            $payment->coin = (int) $package->credit_amount;
            $payment->transaction_type = Payment::TRANSACTION_TYPE_CREDIT;
            $payment->payment_type = Payment::PAYMENT_TYPE_PACKAGE;
            $payment->transaction_id = $reference;
            $payment->remarks = 'dreamland_dev_topup:' . $package->id;
            $payment->save(false);

            $dbTx->commit();
            return [
                'ok' => true,
                'credits_granted' => (int) $package->credit_amount,
                'reference' => $reference,
                'available_coin' => (int) $user->available_coin,
                'dev_mode' => true,
            ];
        } catch (\Throwable $e) {
            $dbTx->rollBack();
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function paystackRequest($method, $path, $body, $secretKey)
    {
        $url = 'https://api.paystack.co/' . ltrim($path, '/');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $secretKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        $raw = curl_exec($ch);
        curl_close($ch);
        return json_decode((string) $raw, true) ?: [];
    }
}
