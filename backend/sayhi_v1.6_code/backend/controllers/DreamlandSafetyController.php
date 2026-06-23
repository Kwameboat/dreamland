<?php

namespace backend\controllers;

use api\modules\v1\models\Post;
use common\components\DreamlandSafetyPipeline;
use common\models\SafetyScanQueue;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;

class DreamlandSafetyController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => ['process-queue' => ['POST']],
            ],
        ];
    }

    public function actionIndex()
    {
        $pendingPosts = new ActiveDataProvider([
            'query' => Post::find()->where(['appraisal_status' => 'pending_safety'])->orderBy(['created_at' => SORT_ASC]),
            'pagination' => ['pageSize' => 20],
        ]);
        $queue = new ActiveDataProvider([
            'query' => SafetyScanQueue::find()->orderBy(['id' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);
        return $this->render('index', ['pendingPosts' => $pendingPosts, 'queue' => $queue]);
    }

    public function actionProcessQueue()
    {
        $limit = (int) Yii::$app->request->post('limit', 25);
        if ($limit < 1) {
            $limit = 25;
        }
        if ($limit > 100) {
            $limit = 100;
        }

        $pipeline = $this->getSafetyPipeline();
        $results = $pipeline->processQueuedJobs($limit);
        $processed = count($results);
        $active = count(array_filter($results, static function ($status) {
            return $status === 'active';
        }));
        $review = count(array_filter($results, static function ($status) {
            return $status === 'pending_review';
        }));

        Yii::$app->session->setFlash(
            'success',
            "Processed {$processed} safety job(s): {$active} published, {$review} sent to appraisal."
        );

        return $this->redirect(['index']);
    }

    private function getSafetyPipeline(): DreamlandSafetyPipeline
    {
        if (Yii::$app->has('dreamlandSafety')) {
            return Yii::$app->dreamlandSafety;
        }

        return new DreamlandSafetyPipeline();
    }
}
