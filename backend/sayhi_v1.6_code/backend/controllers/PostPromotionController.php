<?php

namespace backend\controllers;

// use api\modules\v1\models\PostPromotionSearch;
use Yii;
//use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

use yii\helpers\ArrayHelper;
use common\models\PostPromotion;

use backend\models\PostPromotionSearch;
use yii\filters\AccessControl;
/**
 * 
 */
class PostPromotionController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::PROMOTION),
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all  running promotion.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new PostPromotionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);



        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,

        ]);
    }

    /**
     * 
     * List all completed Promotion complete-promotion
     * 
     */
    public function actionCompletePromotion()
    {
        $searchModel = new PostPromotionSearch();
        $dataProvider = $searchModel->searchCompletePromotion(Yii::$app->request->queryParams);



        return $this->render('complete-promotion', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,

        ]);
    }

    /**
     * Displays a single Driver Document model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model  = $this->findModel($id);

        return $this->render('view', [
            'model' =>   $model
        ]);
    }

    protected function findModel($id)
    {
        if (($model = PostPromotion::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * Displays a update Driver Document model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        
        if ($model->load(Yii::$app->request->post()) ) {
            if( $model->save()){

                Yii::$app->session->setFlash('success', "Post Promotion updated successfully");
                return $this->redirect(['index']);
            }
           
        }
        return $this->render('update', [
            'model' =>   $model
        ]);
    }
}
