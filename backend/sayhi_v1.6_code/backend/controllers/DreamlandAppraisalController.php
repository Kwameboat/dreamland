<?php

namespace backend\controllers;

use api\modules\v1\models\Post;
use common\components\DreamlandContentReview;
use common\models\GroupWatchPot;
use common\models\VideoPrediction;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class DreamlandAppraisalController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => ['class' => VerbFilter::className(), 'actions' => ['evaluate' => ['POST']]],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
        ];
    }

    public function actionIndex()
    {
        $query = Post::find()->where(['appraisal_status' => 'pending_review'])->orderBy(['created_at' => SORT_ASC]);
        $dataProvider = new ActiveDataProvider(['query' => $query, 'pagination' => ['pageSize' => 20]]);
        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    public function actionEvaluate($id)
    {
        $post = Post::findOne($id);
        if (!$post) {
            throw new NotFoundHttpException('Video not found.');
        }

        $status = Yii::$app->request->post('status');
        $priceCredits = (int) Yii::$app->request->post('price_credits', 0);

        if ($status === 'active') {
            if ($priceCredits <= 0) {
                Yii::$app->session->setFlash('error', 'Assign a valid credit price before approving.');
                return $this->redirect(['index']);
            }
            $post->price_credits = $priceCredits;
            $post->appraisal_status = 'active';
            $post->status = Post::STATUS_ACTIVE;

            $pot = new GroupWatchPot([
                'video_id' => $post->id,
                'target_unlocks' => 100,
                'bonus_pool_credits' => max(50, (int) floor($priceCredits * 0.5)),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
            ]);
            $pot->save(false);

            $prediction = new VideoPrediction([
                'video_id' => $post->id,
                'target_metric' => '10000 views',
                'target_value' => 10000,
                'timer_expires_at' => date('Y-m-d H:i:s', strtotime('+3 days')),
            ]);
            $prediction->save(false);
            $post->save(false);
        } else {
            $reason = trim((string) Yii::$app->request->post('rejection_reason', ''));
            if ($reason === '') {
                Yii::$app->session->setFlash('error', 'A rejection reason is required.');
                return $this->redirect(['index']);
            }
            try {
                DreamlandContentReview::rejectPost($post, $reason, (int) Yii::$app->user->id);
            } catch (\InvalidArgumentException $e) {
                Yii::$app->session->setFlash('error', $e->getMessage());
                return $this->redirect(['index']);
            }
        }

        Yii::$app->session->setFlash('success', 'Appraisal updated.');
        return $this->redirect(['index']);
    }
}
