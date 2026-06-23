<?php

namespace backend\controllers;

use api\modules\v1\models\Post;
use common\components\DreamlandContentReview;
use common\models\LocalBlacklistKeyword;
use common\models\SafetyScanQueue;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class DreamlandModerationController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'test' => ['POST'],
                    'add-keyword' => ['POST'],
                    'delete-keyword' => ['POST'],
                    'decide' => ['POST'],
                    'requeue' => ['POST'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [[
                    'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::DREAMLAND_MODERATION),
                    'roles' => ['@'],
                ]],
            ],
        ];
    }

    public function actionIndex()
    {
        $agentHealthy = Yii::$app->has('dreamlandModeration')
            ? Yii::$app->dreamlandModeration->isHealthy()
            : false;
        $agentConfig = Yii::$app->has('dreamlandModeration')
            ? Yii::$app->dreamlandModeration->getConfig()
            : null;
        $agentHealth = Yii::$app->has('dreamlandModeration')
            ? Yii::$app->dreamlandModeration->getHealth()
            : null;
        if (is_array($agentConfig) && is_array($agentHealth)) {
            $agentConfig['gemini'] = $agentHealth['gemini'] ?? ($agentConfig['gemini'] ?? []);
        }

        $queue = new ActiveDataProvider([
            'query' => SafetyScanQueue::find()->orderBy(['id' => SORT_DESC]),
            'pagination' => ['pageSize' => 15],
        ]);

        $flagged = new ActiveDataProvider([
            'query' => Post::find()
                ->where(['appraisal_status' => ['rejected', 'pending_review', 'pending_safety']])
                ->orderBy(['updated_at' => SORT_DESC, 'created_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 15],
        ]);

        return $this->render('index', [
            'agentHealthy' => $agentHealthy,
            'agentConfig' => $agentConfig,
            'queue' => $queue,
            'flagged' => $flagged,
        ]);
    }

    public function actionKeywords()
    {
        $model = new LocalBlacklistKeyword(['is_active' => 1, 'locale' => 'gh', 'severity' => 2]);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Keyword added to Ghana moderation list.');
            return $this->redirect(['keywords']);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => LocalBlacklistKeyword::find()->orderBy(['locale' => SORT_ASC, 'keyword' => SORT_ASC]),
            'pagination' => ['pageSize' => 30],
        ]);

        return $this->render('keywords', [
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
    }

    public function actionTest()
    {
        $text = trim((string) Yii::$app->request->post('text', ''));
        if ($text === '') {
            Yii::$app->session->setFlash('error', 'Enter text to test.');
            return $this->redirect(['index']);
        }

        if (!Yii::$app->has('dreamlandModeration')) {
            Yii::$app->session->setFlash('error', 'Moderation agent component not configured.');
            return $this->redirect(['index']);
        }

        $result = Yii::$app->dreamlandModeration->moderateContent(['text' => $text]);
        if (!$result) {
            Yii::$app->session->setFlash('error', 'Moderation agent is offline. Run start-moderation-agent.ps1');
            return $this->redirect(['index']);
        }

        Yii::$app->session->set('moderation_test_result', $result);
        return $this->redirect(['index']);
    }

    public function actionAddKeyword()
    {
        $model = new LocalBlacklistKeyword();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Keyword saved.');
        } else {
            Yii::$app->session->setFlash('error', 'Could not save keyword.');
        }
        return $this->redirect(['keywords']);
    }

    public function actionDeleteKeyword($id)
    {
        $model = LocalBlacklistKeyword::findOne((int) $id);
        if ($model) {
            $model->delete();
            Yii::$app->session->setFlash('success', 'Keyword removed.');
        }
        return $this->redirect(['keywords']);
    }

    public function actionDecide($id)
    {
        $post = Post::findOne((int) $id);
        if (!$post) {
            throw new NotFoundHttpException('Post not found.');
        }

        $decision = Yii::$app->request->post('decision');
        if ($decision === 'approve') {
            $post->appraisal_status = 'pending_review';
            $post->status = Post::STATUS_BLOCKED;
            if (DreamlandContentReview::hasRejectionColumns()) {
                $post->rejection_reason = null;
                $post->rejected_at = null;
                $post->rejected_by = null;
                $post->appeal_status = null;
                $post->appeal_message = null;
                $post->appeal_submitted_at = null;
            }
            Yii::$app->session->setFlash('success', 'Content cleared moderation — sent to appraisal workspace.');
        } elseif ($decision === 'reject') {
            $reason = trim((string) Yii::$app->request->post('rejection_reason', ''));
            if ($reason === '') {
                Yii::$app->session->setFlash('error', 'Please provide a reason for rejection so the creator can improve or appeal.');
                return $this->redirect(['index']);
            }
            try {
                DreamlandContentReview::rejectPost($post, $reason, (int) Yii::$app->user->id);
                Yii::$app->session->setFlash('success', 'Content rejected and creator notified.');
            } catch (\InvalidArgumentException $e) {
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
            return $this->redirect(['index']);
        } elseif ($decision === 'review') {
            $post->appraisal_status = 'pending_review';
            $post->status = Post::STATUS_BLOCKED;
            Yii::$app->session->setFlash('success', 'Sent to appraisal workspace.');
        }

        $post->save(false);
        return $this->redirect(['index']);
    }

    public function actionRequeue($id)
    {
        $job = SafetyScanQueue::findOne((int) $id);
        if (!$job) {
            throw new NotFoundHttpException('Queue job not found.');
        }
        $job->status = SafetyScanQueue::STATUS_QUEUED;
        $job->result_status = null;
        $job->failure_reason = null;
        $job->processed_at = null;
        $job->save(false);

        $post = Post::findOne((int) $job->video_id);
        if ($post) {
            $post->appraisal_status = 'pending_safety';
            $post->save(false);
        }

        Yii::$app->session->setFlash('success', 'Scan re-queued for AI moderation.');
        return $this->redirect(['index']);
    }
}
