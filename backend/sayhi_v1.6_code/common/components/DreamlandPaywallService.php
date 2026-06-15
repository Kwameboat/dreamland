<?php

namespace common\components;

use api\modules\v1\models\Payment;
use api\modules\v1\models\User;
use common\models\DreamlandSetting;
use common\models\GroupWatchPot;
use common\models\GroupWatchPotContribution;
use common\models\Post;
use common\models\PurchasedLive;
use common\models\PurchasedVideo;
use Yii;
use yii\base\Component;
use yii\db\Transaction;

class DreamlandPaywallService extends Component
{
    /**
     * @param object $post Post model with is_paid, price_credits, appraisal_status, user_id, id
     */
    public function decorateFeedItem($post, $viewerId = null)
    {
        $settings = DreamlandSetting::getSettings();
        $previewSeconds = (int) ($settings->preview_seconds ?? 3);

        $payload = [
            'is_paid' => (int) $post->is_paid === 1,
            'price_credits' => $post->price_credits,
            'appraisal_status' => $post->appraisal_status,
            'preview_seconds' => $previewSeconds,
            'is_unlocked' => true,
            'paywall' => null,
        ];

        if ((int) $post->is_paid !== 1) {
            return $payload;
        }

        if ($viewerId && ((int) $viewerId === (int) $post->user_id || PurchasedVideo::hasPurchase($viewerId, $post->id))) {
            return $payload;
        }

        $payload['is_unlocked'] = false;
        $payload['paywall'] = [
            'title' => 'Unlock full video',
            'message' => 'Unlock full video for ' . (int) $post->price_credits . ' Credits',
            'price_credits' => (int) $post->price_credits,
            'preview_loop_seconds' => $previewSeconds,
        ];

        return $payload;
    }

    /**
     * @param \api\modules\v1\models\UserLiveHistory|\common\models\UserLiveHistory $live
     */
    public function decorateLiveItem($live, $viewerId = null)
    {
        $isMonetized = (int) ($live->is_monetized ?? 0) === 1;
        $priceCredits = isset($live->price_credits) ? (int) $live->price_credits : 0;

        $payload = [
            'is_monetized' => $isMonetized,
            'price_credits' => $isMonetized ? $priceCredits : null,
            'is_unlocked' => true,
            'paywall' => null,
        ];

        if (!$isMonetized || $priceCredits <= 0) {
            return $payload;
        }

        $hostId = (int) $live->user_id;
        if ($viewerId && ((int) $viewerId === $hostId || PurchasedLive::hasPurchase($viewerId, $live->id))) {
            return $payload;
        }

        $payload['is_unlocked'] = false;
        $payload['paywall'] = [
            'title' => 'Unlock live stream',
            'message' => 'Unlock live for ' . $priceCredits . ' Credits',
            'price_credits' => $priceCredits,
        ];

        return $payload;
    }

    public function unlockLive($viewerId, $liveId)
    {
        $live = \api\modules\v1\models\UserLiveHistory::findOne([
            'id' => (int) $liveId,
            'status' => \api\modules\v1\models\UserLiveHistory::STATUS_ONGOING,
        ]);
        if (!$live || (int) ($live->is_monetized ?? 0) !== 1) {
            return ['ok' => false, 'error' => 'Live stream is not available for unlock.'];
        }

        if ((int) $live->user_id === (int) $viewerId) {
            return ['ok' => false, 'error' => 'You are hosting this live.'];
        }

        if (PurchasedLive::hasPurchase($viewerId, $liveId)) {
            return ['ok' => true, 'already_purchased' => true];
        }

        $price = (int) $live->price_credits;
        if ($price <= 0) {
            return ['ok' => false, 'error' => 'Invalid unlock price.'];
        }

        $settings = DreamlandSetting::getSettings();
        $commissionPercent = (int) $settings->platform_commission_percent;
        $platformCommission = (int) floor($price * ($commissionPercent / 100));
        $creatorCredits = $price - $platformCommission;

        /** @var Transaction $tx */
        $tx = Yii::$app->db->beginTransaction();
        try {
            $viewer = User::findOne((int) $viewerId);
            $creator = User::findOne((int) $live->user_id);
            if (!$viewer || !$creator) {
                throw new \RuntimeException('User not found.');
            }
            if ($viewer->available_coin < $price) {
                throw new \RuntimeException('Insufficient credits.');
            }

            $viewer->available_coin -= $price;
            $creator->available_coin += $creatorCredits;
            $viewer->save(false);
            $creator->save(false);

            $purchase = new PurchasedLive([
                'user_id' => (int) $viewerId,
                'live_id' => (int) $liveId,
                'credits_paid' => $price,
                'creator_credits' => $creatorCredits,
                'platform_commission' => $platformCommission,
            ]);
            $purchase->save(false);

            $this->recordPaymentLedger($viewerId, $price, Payment::TRANSACTION_TYPE_DEBIT, 'live_unlock', $liveId);
            $this->recordPaymentLedger($creator->id, $creatorCredits, Payment::TRANSACTION_TYPE_CREDIT, 'live_sale', $liveId);

            $tx->commit();
            return [
                'ok' => true,
                'credits_spent' => $price,
                'creator_received' => $creatorCredits,
                'platform_commission' => $platformCommission,
            ];
        } catch (\Throwable $e) {
            $tx->rollBack();
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function unlockVideo($viewerId, $videoId)
    {
        $post = Post::findOne(['id' => (int) $videoId, 'appraisal_status' => 'active']);
        if (!$post || (int) $post->is_paid !== 1) {
            return ['ok' => false, 'error' => 'Video is not available for unlock.'];
        }

        if ((int) $post->user_id === (int) $viewerId) {
            return ['ok' => false, 'error' => 'You already own this video.'];
        }

        if (PurchasedVideo::hasPurchase($viewerId, $videoId)) {
            return ['ok' => true, 'already_purchased' => true];
        }

        $price = (int) $post->price_credits;
        if ($price <= 0) {
            return ['ok' => false, 'error' => 'Invalid unlock price.'];
        }

        $settings = DreamlandSetting::getSettings();
        $commissionPercent = (int) $settings->platform_commission_percent;
        $platformCommission = (int) floor($price * ($commissionPercent / 100));
        $creatorCredits = $price - $platformCommission;

        /** @var Transaction $tx */
        $tx = Yii::$app->db->beginTransaction();
        try {
            $viewer = User::findOne((int) $viewerId);
            $creator = User::findOne((int) $post->user_id);
            if (!$viewer || !$creator) {
                throw new \RuntimeException('User not found.');
            }
            if ($viewer->available_coin < $price) {
                throw new \RuntimeException('Insufficient credits.');
            }

            $viewer->available_coin -= $price;
            $creator->available_coin += $creatorCredits;
            $viewer->save(false);
            $creator->save(false);

            $purchase = new PurchasedVideo([
                'user_id' => (int) $viewerId,
                'video_id' => (int) $videoId,
                'credits_paid' => $price,
                'creator_credits' => $creatorCredits,
                'platform_commission' => $platformCommission,
            ]);
            $purchase->save(false);

            $this->recordPaymentLedger($viewerId, $price, Payment::TRANSACTION_TYPE_DEBIT, 'video_unlock', $videoId);
            $this->recordPaymentLedger($creator->id, $creatorCredits, Payment::TRANSACTION_TYPE_CREDIT, 'video_sale', $videoId);

            $this->incrementWatchPot($videoId, $viewerId, $price);

            $tx->commit();
            return [
                'ok' => true,
                'credits_spent' => $price,
                'creator_received' => $creatorCredits,
                'platform_commission' => $platformCommission,
            ];
        } catch (\Throwable $e) {
            $tx->rollBack();
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function recordPaymentLedger($userId, $coin, $transactionType, $reference, $videoId)
    {
        $payment = new Payment();
        $payment->type = Payment::TYPE_COIN;
        $payment->user_id = (int) $userId;
        $payment->coin = $coin;
        $payment->transaction_type = $transactionType;
        $payment->payment_type = Payment::PAYMENT_TYPE_COIN_TRANSFER;
        $payment->payment_mode = Payment::PAYMENT_MODE_WALLET;
        $payment->detail_reference_id = (int) $videoId;
        $payment->remarks = $reference;
        $payment->save(false);
    }

    private function incrementWatchPot($videoId, $userId, $credits)
    {
        $pot = GroupWatchPot::findOne(['video_id' => (int) $videoId, 'status' => GroupWatchPot::STATUS_OPEN]);
        if (!$pot) {
            return;
        }

        $existing = GroupWatchPotContribution::findOne(['pot_id' => $pot->id, 'user_id' => (int) $userId]);
        if (!$existing) {
            $pot->current_unlocks += 1;
            $contribution = new GroupWatchPotContribution([
                'pot_id' => $pot->id,
                'user_id' => (int) $userId,
                'video_id' => (int) $videoId,
                'credits_contributed' => $credits,
            ]);
            $contribution->save(false);
        } else {
            $existing->credits_contributed += $credits;
            $existing->save(false);
        }

        if ($pot->current_unlocks >= $pot->target_unlocks) {
            $this->distributeWatchPotBonus($pot);
        } else {
            $pot->save(false);
        }
    }

    private function distributeWatchPotBonus(GroupWatchPot $pot)
    {
        $contributions = GroupWatchPotContribution::find()->where(['pot_id' => $pot->id])->all();
        if (!$contributions) {
            return;
        }

        $pool = (int) $pot->bonus_pool_credits;
        $weights = [];
        $totalWeight = 0;
        foreach ($contributions as $row) {
            $weight = max(1, (int) $row->credits_contributed);
            $weights[$row->id] = $weight;
            $totalWeight += $weight;
        }

        $remaining = $pool;
        $lastId = array_key_last($weights);
        foreach ($contributions as $row) {
            $share = ($row->id === $lastId)
                ? $remaining
                : (int) floor($pool * ($weights[$row->id] / $totalWeight));
            $remaining -= $share;
            $row->bonus_received = $share;
            $row->save(false);

            $user = User::findOne($row->user_id);
            if ($user && $share > 0) {
                $user->available_coin += $share;
                $user->save(false);
            }
        }

        $pot->status = GroupWatchPot::STATUS_COMPLETED;
        $pot->save(false);
    }
}
