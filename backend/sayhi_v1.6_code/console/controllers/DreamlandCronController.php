<?php

namespace console\controllers;

use api\modules\v1\models\Post;
use api\modules\v1\models\User;
use common\models\GroupWatchPot;
use common\models\VideoPrediction;
use common\models\VideoPredictionStake;
use Yii;
use yii\console\Controller;

class DreamlandCronController extends Controller
{
    /**
     * Resolves open prediction markets and pays 2x stake to winners.
     * Run every 15 minutes: php yii dreamland-cron/resolve-predictions
     */
    public function actionResolvePredictions()
    {
        $predictions = VideoPrediction::find()
            ->where(['status' => VideoPrediction::STATUS_OPEN])
            ->andWhere(['<=', 'timer_expires_at', date('Y-m-d H:i:s')])
            ->all();

        foreach ($predictions as $prediction) {
            $post = Post::findOne($prediction->video_id);
            $hit = $post && (int) $post->total_view >= (int) $prediction->target_value;
            $prediction->status = VideoPrediction::STATUS_RESOLVED;
            $prediction->outcome = $hit ? 'hit' : 'miss';
            $prediction->save(false);

            $stakes = VideoPredictionStake::find()->where(['prediction_id' => $prediction->id])->all();
            foreach ($stakes as $stake) {
                $won = ($hit && $stake->prediction_side === 'yes') || (!$hit && $stake->prediction_side === 'no');
                if (!$won) {
                    continue;
                }
                $payout = (int) $stake->stake_credits * 2;
                $stake->payout_credits = $payout;
                $stake->save(false);
                $user = User::findOne($stake->user_id);
                if ($user) {
                    $user->available_coin += $payout;
                    $user->save(false);
                }
            }
        }

        $this->stdout("Resolved " . count($predictions) . " prediction markets.\n");
    }

    /**
     * Expire stale group watch pots.
     */
    public function actionExpireWatchPots()
    {
        $pots = GroupWatchPot::find()
            ->where(['status' => GroupWatchPot::STATUS_OPEN])
            ->andWhere(['<=', 'expires_at', date('Y-m-d H:i:s')])
            ->all();
        foreach ($pots as $pot) {
            $pot->status = GroupWatchPot::STATUS_EXPIRED;
            $pot->save(false);
        }
        $this->stdout('Expired ' . count($pots) . " watch pots.\n");
    }

    /**
     * Reset streaks for users who missed a day without freeze protection.
     */
    public function actionResetStreaks()
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $users = User::find()
            ->where(['not', ['last_active_date' => null]])
            ->andWhere(['<', 'last_active_date', $yesterday])
            ->andWhere(['>', 'current_streak', 0])
            ->all();

        $reset = 0;
        foreach ($users as $user) {
            $frozen = $user->streak_frozen_until && $user->streak_frozen_until >= $today;
            if ($frozen) {
                continue;
            }
            $user->current_streak = 0;
            $user->daily_watch_seconds = 0;
            $user->daily_game_score = 0;
            $user->save(false);
            $reset++;
        }
        $this->stdout("Reset streaks for {$reset} users.\n");
    }
}
