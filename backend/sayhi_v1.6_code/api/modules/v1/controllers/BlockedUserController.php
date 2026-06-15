<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use api\modules\v1\models\FavoriteAd;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use api\modules\v1\models\BlockedUser;
use api\modules\v1\models\User;
use api\modules\v1\models\Notification;

class BlockedUserController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\blockedUser';   
    
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

    
    public function actionIndex()
    {
        $userId = Yii::$app->user->identity->id;
        $model = new BlockedUser();
        $query = $model->find()->where(['user_id'=>$userId])
        ->with(['blockedUserDetail'=> function ($query) {
            $query->select(['user.id','user.username','user.email','user.bio','user.description','user.image','user.is_verified','user.country_code','user.phone','user.country','user.city','user.sex','user.dob','user.is_chat_user_online','user.chat_last_time_online']);
            
        }]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['blockedUser']= $dataProvider;
        return $response;
       
    }

    

    public function actionCreate()
    {
        $model = new BlockedUser();
        $userId = Yii::$app->user->identity->id;
        $model->scenario ='create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
        $blockedUserId   =  @(int) $model->blocked_user_id;
        $totalCount = $model->find()->where(['blocked_user_id'=>$blockedUserId, 'user_id'=>$userId])->count();
        if($totalCount>0){
           $response['statusCode']=422;
           $errors['message'][] =   Yii::$app->params['apiMessage']['blockedUser']['alreadyBlocked'];
           $response['errors']=$errors;
          return $response; 
        }
        if($model->save(false)){
            $response['message']=Yii::$app->params['apiMessage']['blockedUser']['blocked'];
             return $response; 
        }else{
             $response['statusCode']=422;
             $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
             $response['errors']=$errors;
             return $response; 
         }
    }

    public function actionUnBlocked()
    {
        $model = new BlockedUser();
        $userId = Yii::$app->user->identity->id;
        $model->scenario ='create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
        
            return $response;
        }
        $blockedUserId   =  @(int) $model->blocked_user_id;
        $result = $model->find()->where(['blocked_user_id'=>$blockedUserId, 'user_id'=>$userId])->one();
        if(isset($result->id)){
            if($result->delete()){
                $response['message']=Yii::$app->params['apiMessage']['blockedUser']['unBlocked'];
                return $response; 
            }else{
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response; 
            }
        }else{
            $response['statusCode']=422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors']=$errors;
            return $response; 
        }
    }
}


