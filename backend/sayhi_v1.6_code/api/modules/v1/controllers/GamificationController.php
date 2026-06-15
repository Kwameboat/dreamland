<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\Post;
use api\modules\v1\models\User;
use common\components\DreamlandStreakService;
use common\models\CreditPackage;
use common\models\GroupWatchPot;
use common\models\VideoPrediction;
use common\models\VideoPredictionStake;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\ActiveController;

class GamificationController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\Post';

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
            'except' => ['open-predictions'],
            'authMethods' => [HttpBearerAuth::className()],
        ];
        return $behaviors;
    }

    public function actionStreakStatus()
    {
        /** @var DreamlandStreakService $service */
        $service = Yii::$app->dreamlandStreak;
        return ['message' => 'ok', 'streak' => $service->getStreakStatus(Yii::$app->user->identity->id)];
    }

    public function actionRecordWatch()
    {
        $body = Yii::$app->request->bodyParams;
        $seconds = (int) ($body['seconds'] ?? 0);
        $postId = (int) ($body['post_id'] ?? 0);
        $watchMs = max(0, (int) ($body['watch_ms'] ?? ($seconds * 1000)));
        $completionPct = min(100, max(0, (int) ($body['completion_pct'] ?? 0)));
        if ($seconds <= 0 && $watchMs > 0) {
            $seconds = max(1, (int) ceil($watchMs / 1000));
        }

        /** @var DreamlandStreakService $service */
        $service = Yii::$app->dreamlandStreak;
        $userId = (int) Yii::$app->user->identity->id;
        $streak = $service->recordWatchSeconds($userId, $seconds);

        if ($postId > 0) {
            $schema = Yii::$app->db->schema->getTableSchema('post_watch_events', true);
            if ($schema) {
                Yii::$app->db->createCommand()->insert('post_watch_events', [
                    'user_id' => $userId,
                    'post_id' => $postId,
                    'watch_ms' => $watchMs,
                    'completion_pct' => $completionPct,
                    'rewatched' => !empty($body['rewatched']) ? 1 : 0,
                    'created_at' => time(),
                ])->execute();
            }
        }

        return ['message' => 'ok', 'streak' => $streak];
    }

    public function actionRecordGameScore()
    {
        $score = (int) (Yii::$app->request->getBodyParam('score', 0));
        /** @var DreamlandStreakService $service */
        $service = Yii::$app->dreamlandStreak;
        return ['message' => 'ok', 'streak' => $service->recordGameScore(Yii::$app->user->identity->id, $score)];
    }

    public function actionFreezeStreak()
    {
        /** @var DreamlandStreakService $service */
        $service = Yii::$app->dreamlandStreak;
        $result = $service->freezeStreak(Yii::$app->user->identity->id);
        if (!$result['ok']) {
            return ['statusCode' => 422, 'message' => $result['error']];
        }
        return ['message' => 'Streak frozen.', 'data' => $result];
    }

    public function actionOpenPredictions()
    {
        $predictions = VideoPrediction::find()
            ->where(['status' => VideoPrediction::STATUS_OPEN])
            ->andWhere(['>', 'timer_expires_at', date('Y-m-d H:i:s')])
            ->limit(50)
            ->all();
        return ['message' => 'ok', 'predictions' => $predictions];
    }

    public function actionStakePrediction()
    {
        $body = Yii::$app->request->getBodyParams();
        $predictionId = (string) ($body['prediction_id'] ?? '');
        $stakeCredits = (int) ($body['stake_credits'] ?? 0);
        $side = (string) ($body['prediction_side'] ?? 'yes');
        $userId = Yii::$app->user->identity->id;

        if (!$predictionId || !in_array($stakeCredits, [1, 2], true)) {
            return ['statusCode' => 422, 'message' => 'prediction_id and stake_credits (1 or 2) are required.'];
        }

        $prediction = VideoPrediction::findOne(['id' => $predictionId, 'status' => VideoPrediction::STATUS_OPEN]);
        if (!$prediction) {
            return ['statusCode' => 404, 'message' => 'Prediction market not found or closed.'];
        }

        $user = User::findOne($userId);
        if ($user->available_coin < $stakeCredits) {
            return ['statusCode' => 422, 'message' => 'Insufficient credits.'];
        }

        $existing = VideoPredictionStake::findOne(['prediction_id' => $predictionId, 'user_id' => $userId]);
        if ($existing) {
            return ['statusCode' => 422, 'message' => 'You already staked on this prediction.'];
        }

        $user->available_coin -= $stakeCredits;
        $user->save(false);

        $stake = new VideoPredictionStake([
            'prediction_id' => $predictionId,
            'user_id' => $userId,
            'stake_credits' => $stakeCredits,
            'prediction_side' => in_array($side, ['yes', 'no'], true) ? $side : 'yes',
        ]);
        $stake->save(false);

        return ['message' => 'Stake placed.', 'stake' => $stake];
    }

    /**
     * GET /v1/gamification/watch-pot?video_id=
     */
    public function actionWatchPot()
    {
        $videoId = (int) Yii::$app->request->get('video_id', 0);
        if (!$videoId) {
            return ['statusCode' => 422, 'message' => 'video_id is required.'];
        }
        $pot = GroupWatchPot::findOne(['video_id' => $videoId, 'status' => GroupWatchPot::STATUS_OPEN]);
        if (!$pot) {
            return ['message' => 'No active watch pot for this reel.', 'pot' => null];
        }
        return [
            'message' => 'ok',
            'pot' => [
                'video_id' => (int) $pot->video_id,
                'target_unlocks' => (int) $pot->target_unlocks,
                'current_unlocks' => (int) $pot->current_unlocks,
                'bonus_pool_credits' => (int) $pot->bonus_pool_credits,
                'expires_at' => $pot->expires_at,
                'progress_percent' => $pot->target_unlocks > 0
                    ? min(100, (int) floor(($pot->current_unlocks / $pot->target_unlocks) * 100))
                    : 0,
            ],
        ];
    }

    /**
     * GET /v1/gamification/predictions-for-video?video_id=
     */
    public function actionPredictionsForVideo()
    {
        $videoId = (int) Yii::$app->request->get('video_id', 0);
        if (!$videoId) {
            return ['statusCode' => 422, 'message' => 'video_id is required.'];
        }
        $predictions = VideoPrediction::find()
            ->where(['video_id' => $videoId, 'status' => VideoPrediction::STATUS_OPEN])
            ->andWhere(['>', 'timer_expires_at', date('Y-m-d H:i:s')])
            ->all();
        return ['message' => 'ok', 'predictions' => $predictions];
    }
}
