<?php

namespace backend\controllers;

use api\modules\v1\models\Post;
use common\components\DreamlandAppraisalService;
use common\components\DreamlandContentReview;
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
            if ($priceCredits <= 0 && (int) $post->is_paid === 1) {
                Yii::$app->session->setFlash('error', 'Assign a valid credit price before approving.');
                return $this->redirect(['index']);
            }
            try {
                DreamlandAppraisalService::approvePost($post, $priceCredits);
            } catch (\Throwable $e) {
                Yii::error($e->getMessage(), __METHOD__);
                Yii::$app->session->setFlash('error', 'Could not approve video: ' . $e->getMessage());
                return $this->redirect(['index']);
            }
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
