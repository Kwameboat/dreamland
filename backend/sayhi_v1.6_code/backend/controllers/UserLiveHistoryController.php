<?php

namespace backend\controllers;

use Yii;
//use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\UserLiveHistory;
use backend\models\GiftHistorySearch;
use backend\models\UserLiveHistorySearch;
use yii\web\UploadedFile;
use common\models\FileUpload;
use common\models\GiftCategory;
use common\models\GiftHistory;
use common\models\User;
use common\models\UserLiveBattle;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;

/**
 * 
 */
class UserLiveHistoryController extends Controller
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
                        'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::LIVE_HISTORY),
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
       
    }

    /**
     * Lists all  models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new UserLiveHistorySearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $modelUser = new User();
        $resultUser = $modelUser->find()->select(['id','username'])->where(['role' => User::ROLE_CUSTOMER])->andWhere(['<>', 'status', User::STATUS_DELETED])->all(); 
        $userData = ArrayHelper::map($resultUser,'id','username');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'userData' => $userData
        ]);
    }

    /**
     * Displays a single Countryy model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model  = new UserLiveHistory();
        $modelGift = new GiftHistory();
        $modelBattle = new UserLiveBattle();

        $result = $model->find()
        ->joinWith('giftDetails')
        ->where(['user_live_history.id'=>$id])
        ->andWhere(['user_live_history.status'=>UserLiveHistory::STATUS_COMPLETED])->one();

        $dataProvider = $modelBattle->find()
        // ->joinWith('userBattle')
        ->where(['user_live_history_id' => $id])
        ->andWhere(['status'=>UserLiveBattle::STATUS_COMPLETED]);
        $dataProvider = new ActiveDataProvider([
            'query' => $dataProvider
        ]);
        return $this->render('view', [
            'model' =>   $result,
            'dataProvider' =>$dataProvider
        ]);
    }

    /**
     * 
     * get dynamic gift coin details by live id and battle id
     * 
     */
    public function actionLivegiftHistoryDetail($liveCallId,$recieverId,$battleId=null){
        $modelGift = new GiftHistory();
        $query = $modelGift->find()
        ->where(['live_call_id'=>$liveCallId ,'reciever_id'=>$recieverId]);
        if($battleId !=null){
            $query->andWhere(['battle_id'=>$battleId]);
        }
       
        // ->all();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 10, // Set the number of items per page
            ],
        ]);
        return $this->renderPartial('user-live-gift-details', [
            'dataProvider' => $dataProvider,
        ]);
        // return $this->renderPartial('dynamic-data', ['data' => $result]);
    }

    /**
     * Finds the Countryy model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Countryy the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = UserLiveHistory::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}