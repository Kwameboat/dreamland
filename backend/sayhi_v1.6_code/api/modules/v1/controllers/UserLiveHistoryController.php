<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;

use api\modules\v1\models\UserLiveHistory;
use api\modules\v1\models\User;
use yii\web\UploadedFile;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use api\modules\v1\models\GiftHistory;


class UserLiveHistoryController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\userLiveHistory';   

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];
    
    
    public function actions()
	{
		$actions = parent::actions();

		// disable default actions
		unset($actions['create'], $actions['update'], $actions['index'], $actions['delete'], $actions['view']);                    

		return $actions;
	}    

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),
            'except'=>[],
            'authMethods' => [
                HttpBearerAuth::className()
            ],
        ];
        return $behaviors;
    }



    

    // live  user 

    public function actionIndex()
    {
        $userId     =     Yii::$app->user->identity->id;
        $model      =   new UserLiveHistory();
        $query = $model->find()
         ->where(['user_id'=>$userId]);
        //$user = $query->all();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['live_history']=$dataProvider;
        return $response; 

    }

    public function actionView($id)
    {
        
        $model      =   new UserLiveHistory();
        $result = $model->findOne($id);
       
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['live_history']=$result;
        return $response; 

    }
    public function actionDetail($id)
    {
        
        $model      =   new UserLiveHistory();
        $result = $model->find()->where(['channel_name'=>$id])->one();
       
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['live_history']=$result;
        return $response; 

    }




}


