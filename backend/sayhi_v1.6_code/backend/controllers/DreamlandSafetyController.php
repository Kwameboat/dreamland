<?php

namespace backend\controllers;

use api\modules\v1\models\Post;
use common\models\SafetyScanQueue;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
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
}
