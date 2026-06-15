<?php

namespace common\components;

use api\modules\v1\models\User;
use common\models\DreamlandSetting;
use common\models\StreakMilestoneReward;
use Yii;
use yii\base\Component;

class DreamlandStreakService extends Component
{
    private static $milestones = [
        3 => 1.0,
        7 => 3.0,
        30 => 10.0,
    ];

    public function recordWatchSeconds($userId, $seconds)
    {
        $user = User::findOne((int) $userId);
        if (!$user) {
            return null;
        }
        $user->daily_watch_seconds = (int) $user->daily_watch_seconds + (int) $seconds;
        $user->save(false);
        return $this->evaluateDailyCompletion($user);
    }

    public function recordGameScore($userId, $score)
    {
        $user = User::findOne((int) $userId);
        if (!$user) {
            return null;
        }
        $user->daily_game_score = max((int) $user->daily_game_score, (int) $score);
        $user->save(false);
        return $this->evaluateDailyCompletion($user);
    }

    public function freezeStreak($userId)
    {
        $settings = DreamlandSetting::getSettings();
        $cost = (int) $settings->streak_freeze_cost;
        $user = User::findOne((int) $userId);
        if (!$user) {
            return ['ok' => false, 'error' => 'User not found.'];
        }
        if ($user->available_coin < $cost) {
            return ['ok' => false, 'error' => 'Insufficient credits to freeze streak.'];
        }
        $user->available_coin -= $cost;
        $user->streak_frozen_until = date('Y-m-d', strtotime('+1 day'));
        $user->save(false);
        return ['ok' => true, 'frozen_until' => $user->streak_frozen_until];
    }

    public function getStreakStatus($userId)
    {
        $user = User::findOne((int) $userId);
        if (!$user) {
            return null;
        }
        $settings = DreamlandSetting::getSettings();
        return [
            'current_streak' => (int) $user->current_streak,
            'last_active_date' => $user->last_active_date,
            'daily_watch_seconds' => (int) $user->daily_watch_seconds,
            'daily_game_score' => (int) $user->daily_game_score,
            'watch_threshold_seconds' => (int) $settings->streak_watch_threshold_seconds,
            'game_score_threshold' => (int) $settings->streak_game_score_threshold,
            'freeze_cost' => (int) $settings->streak_freeze_cost,
            'milestones' => self::$milestones,
        ];
    }

    private function evaluateDailyCompletion(User $user)
    {
        $settings = DreamlandSetting::getSettings();
        $today = date('Y-m-d');

        if ($user->last_active_date === $today) {
            return $this->getStreakStatus($user->id);
        }

        $watchMet = (int) $user->daily_watch_seconds >= (int) $settings->streak_watch_threshold_seconds;
        $gameMet = (int) $user->daily_game_score >= (int) $settings->streak_game_score_threshold;
        if (!$watchMet && !$gameMet) {
            return $this->getStreakStatus($user->id);
        }

        $yesterday = date('Y-m-d', strtotime('-1 day'));
        if ($user->last_active_date === $yesterday || ($user->streak_frozen_until && $user->streak_frozen_until >= $today)) {
            $user->current_streak = (int) $user->current_streak + 1;
        } elseif ($user->last_active_date !== $today) {
            $user->current_streak = 1;
        }

        $user->last_active_date = $today;
        $user->daily_watch_seconds = 0;
        $user->daily_game_score = 0;
        $user->streak_frozen_until = null;
        $user->save(false);

        $this->awardMilestoneIfNeeded($user);
        return $this->getStreakStatus($user->id);
    }

    private function awardMilestoneIfNeeded(User $user)
    {
        $day = (int) $user->current_streak;
        if (!isset(self::$milestones[$day])) {
            return;
        }

        $exists = StreakMilestoneReward::find()
            ->where(['user_id' => $user->id, 'milestone_day' => $day])
            ->exists();
        if ($exists) {
            return;
        }

        $reward = self::$milestones[$day];
        $user->available_coin += $reward;
        $user->save(false);

        $record = new StreakMilestoneReward([
            'user_id' => $user->id,
            'milestone_day' => $day,
            'credits_awarded' => $reward,
        ]);
        $record->save(false);
    }
}
