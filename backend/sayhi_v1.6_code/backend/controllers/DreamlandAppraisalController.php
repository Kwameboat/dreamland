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
        $query = Post::find()
            ->where(['appraisal_status' => 'pending_review'])
            ->orderBy(['created_at' => SORT_ASC]);
        $dataProvider = new ActiveDataProvider(['query' => $query, 'pagination' => ['pageSize' => 20]]);
        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    public function actionPreview($id)
    {
        $post = Post::findOne((int) $id);
        if (!$post) {
            throw new NotFoundHttpException('Video not found.');
        }

        return $this->render('preview', ['post' => $post]);
    }

    public function actionEvaluate($id)
    {
        try {
            $post = Post::findOne($id);
            if (!$post) {
                throw new NotFoundHttpException('Video not found.');
            }

            $status = Yii::$app->request->post('status');
            $priceCredits = (int) Yii::$app->request->post('price_credits', 0);

            if ($status === 'active') {
                $isPaid = (int) $post->is_paid === 1;
                if ($isPaid && $priceCredits <= 0) {
                    Yii::$app->session->setFlash('error', 'Assign a valid credit price before approving premium content.');
                    return $this->redirect(['index']);
                }
                DreamlandAppraisalService::approvePost($post, $priceCredits);
            } else {
                $reason = trim((string) Yii::$app->request->post('rejection_reason', ''));
                if ($reason === '') {
                    Yii::$app->session->setFlash('error', 'A rejection reason is required.');
                    return $this->redirect(['index']);
                }
                DreamlandContentReview::rejectPost($post, $reason, (int) Yii::$app->user->id);
            }

            Yii::$app->session->setFlash('success', 'Appraisal updated.');
            return $this->redirect(['index']);
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'Could not update appraisal: ' . $e->getMessage());
            return $this->redirect(['index']);
        }
    }
}
