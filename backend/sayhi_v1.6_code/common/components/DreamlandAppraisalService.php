<?php

namespace common\components;

use common\models\GroupWatchPot;
use common\models\Post;
use common\models\VideoPrediction;
use Yii;
use yii\base\Component;

/**
 * Shared premium content appraisal approval (admin UI + API).
 */
class DreamlandAppraisalService extends Component
{
    /**
     * @param Post|\api\modules\v1\models\Post $post
     */
    public static function approvePost($post, int $priceCredits): void
    {
        $isPaid = (int) ($post->is_paid ?? 0) === 1;
        if ($isPaid && $priceCredits <= 0) {
            throw new \InvalidArgumentException('Assign a valid credit price before approving paid content.');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if ($isPaid) {
                $post->price_credits = $priceCredits;
                self::ensureGamificationTables();
                self::upsertWatchPot((int) $post->id, $priceCredits);
                self::upsertPrediction((int) $post->id);
            }

            $post->appraisal_status = 'active';
            $post->status = Post::STATUS_ACTIVE;
            if (!$post->save(false)) {
                throw new \RuntimeException('Could not save video appraisal.');
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public static function ensureGamificationTables(): void
    {
        $schema = Yii::$app->db->schema->getTableSchema('group_watch_pots', true);
        if ($schema !== null) {
            return;
        }

        $sqlFile = Yii::getAlias('@common/../doc/db/dreamland_gamification_mysql.sql');
        if (!is_file($sqlFile)) {
            throw new \RuntimeException('Gamification tables are missing and migration SQL was not found.');
        }

        $sql = file_get_contents($sqlFile);
        if ($sql === false || trim($sql) === '') {
            throw new \RuntimeException('Gamification migration SQL is empty.');
        }

        foreach (array_filter(array_map('trim', preg_split('/;\s*\n/', $sql))) as $statement) {
            if ($statement === '' || stripos($statement, 'SET ') === 0) {
                continue;
            }
            try {
                Yii::$app->db->createCommand($statement)->execute();
            } catch (\Throwable $e) {
                if (stripos($e->getMessage(), 'already exists') === false
                    && stripos($e->getMessage(), 'Duplicate') === false) {
                    throw $e;
                }
            }
        }

        Yii::$app->db->schema->refresh();
    }

    private static function upsertWatchPot(int $videoId, int $priceCredits): void
    {
        $pot = GroupWatchPot::findOne(['video_id' => $videoId]);
        if (!$pot) {
            $pot = new GroupWatchPot();
            $pot->video_id = $videoId;
        }

        $pot->target_unlocks = 100;
        $pot->current_unlocks = (int) ($pot->current_unlocks ?? 0);
        $pot->bonus_pool_credits = max(50, (int) floor($priceCredits * 0.5));
        $pot->expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
        $pot->status = GroupWatchPot::STATUS_OPEN;

        if (!$pot->save(false)) {
            throw new \RuntimeException('Could not save group watch pot.');
        }
    }

    private static function upsertPrediction(int $videoId): void
    {
        $prediction = VideoPrediction::find()
            ->where(['video_id' => $videoId, 'status' => VideoPrediction::STATUS_OPEN])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();
        if (!$prediction) {
            $prediction = new VideoPrediction();
            $prediction->video_id = $videoId;
        }

        $prediction->target_metric = '10000 views';
        $prediction->target_value = 10000;
        $prediction->timer_expires_at = date('Y-m-d H:i:s', strtotime('+3 days'));
        $prediction->status = VideoPrediction::STATUS_OPEN;
        $prediction->outcome = 'pending';

        if (!$prediction->save(false)) {
            throw new \RuntimeException('Could not save video prediction.');
        }
    }
}
