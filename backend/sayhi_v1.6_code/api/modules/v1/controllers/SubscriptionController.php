<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use api\modules\v1\models\SubscriptionPlan;
use api\modules\v1\models\SubscriptionPlanUser;
use api\modules\v1\models\SubscriptionPlanSubscriber;
use api\modules\v1\models\Payment;
use api\modules\v1\models\User;
use api\modules\v1\models\Follower;
use api\modules\v1\models\BlockedUser;
use api\modules\v1\models\Notification;




use api\modules\v1\models\Post;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
class SubscriptionController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\subscriptionPlan';   

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


    public function actionSubscriptionPlan()
    {
        $userId    =     Yii::$app->user->identity->id;
        $modelSubscriptionPlan  =   new SubscriptionPlan();
        $results =$modelSubscriptionPlan->find()->all();
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['subscription_plan'] = $results;
        return $response;
    }
    public function actionAddPlan()
    {
        $userId     =     Yii::$app->user->identity->id;
        $model      =   new SubscriptionPlanUser();
        $model->scenario = 'addUpdate';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');

        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
      
        $model->addUpdate($model->subscription_plan);
        $response['message']=Yii::$app->params['apiMessage']['subscription']['planAdded'];
       // $response['subscription_plan'] = 'done';// $results;
        return $response;
    }

    public function actionCreate()
    {
        $userId     =     Yii::$app->user->identity->id;
        $model      =   new SubscriptionPlanSubscriber();
        $modelFollower = new Follower();
        $modelSubscriptionPlanUser      =   new SubscriptionPlanUser();

        $model->scenario = 'subscribe';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');

        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
        $resultSubscriptionPlanUser = $modelSubscriptionPlanUser->findOne($model->subscription_plan_user_id);
        if($resultSubscriptionPlanUser){
            $currentTime = time();
            
            $expiryTime = $resultSubscriptionPlanUser->subscriptionPlan->expiryTime;
            $subscribeToUserId = $resultSubscriptionPlanUser->user_id;
            $value = $resultSubscriptionPlanUser->value;
            
            $modelUser = new User();
            $resultUer = $modelUser->findOne($userId);
            
            if ($resultUer->available_coin < $value) {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['payment']['notEnoughBalance'];
                $response['errors'] = $errors;
                return $response;
            }


            $modelSubscriptionPlanSubscriber =  $model->find()->where(['subcriber_id'=>$userId,'subscribe_to_user_id'=>$subscribeToUserId])->one();
            //print_r($resultSubscriptionPlanSubscriber);
            if($modelSubscriptionPlanSubscriber){
                $expiryTimePre =  $modelSubscriptionPlanSubscriber->expiry_time;
                if($expiryTimePre>$currentTime){
                   $leftSecond = $expiryTimePre - $currentTime;
                   $expiryTime = $expiryTime+$leftSecond;
                }
            }else{
                $modelSubscriptionPlanSubscriber      =   new SubscriptionPlanSubscriber();
            }

            $modelSubscriptionPlanSubscriber->subscription_plan_user_id   = $model->subscription_plan_user_id;
            $modelSubscriptionPlanSubscriber->subscribe_to_user_id        = $subscribeToUserId;
            $modelSubscriptionPlanSubscriber->subscription_plan_value     = $value;
            $modelSubscriptionPlanSubscriber->expiry_time                 = $expiryTime;
            if($modelSubscriptionPlanSubscriber->save(false)){
                $resultUer->available_coin = $resultUer->available_coin - $value;
                if ($resultUer->save(false)) {
        
                    // detuct coin from wallet
                    $modelPayment = new Payment();
                    $modelPayment->user_id = $userId;
                    $modelPayment->type = Payment::TYPE_COIN;
                    $modelPayment->coin = $value;
        
                    $modelPayment->transaction_type = Payment::TRANSACTION_TYPE_DEBIT;
                    $modelPayment->payment_type = Payment::PAYMENT_TYPE_USER_SUBSCRIPTIOM;
                    $modelPayment->payment_mode = Payment::PAYMENT_MODE_WALLET;
                    $modelPayment->detail_reference_id = $modelSubscriptionPlanSubscriber->id;
                    
                    $modelPayment->save(false);
        
                   // add coin from wallet
                    $resultUserMain = $modelUser->findOne($subscribeToUserId);
                    $resultUserMain->available_coin = $resultUserMain->available_coin + $value;
                    if ($resultUserMain->save(false)) {
                       
                        $modelPayment = new Payment();
                        $modelPayment->user_id = $subscribeToUserId;
                        $modelPayment->type = Payment::TYPE_COIN;
                        $modelPayment->coin = $value;
            
                        $modelPayment->transaction_type = Payment::TRANSACTION_TYPE_CREDIT;
                        $modelPayment->payment_type = Payment::PAYMENT_TYPE_USER_SUBSCRIPTIOM;
                        $modelPayment->payment_mode = Payment::PAYMENT_MODE_WALLET;
                        $modelPayment->detail_reference_id = $modelSubscriptionPlanSubscriber->id;
                        
                        $modelPayment->save(false);
                    }

                }
                //follow user
                
                $followResult = $modelFollower->find()->where(['follower_id'=>$userId, 'user_id'=>$subscribeToUserId])->one();
                if($followResult){
                    if($followResult->type == Follower::FOLLOW_REQUEST){
                        $followResult->type =  Follower::FOLLOW_PUBLIC;
                        $followResult->save();
                    }
                }else{
                
                    $modelFollower->user_id = $subscribeToUserId;
                    $modelFollower->save();
                
                }

                
                // send notification 
           
                $modelNotification = new Notification();
                $notificationInput = [];
                $notificationData =  Yii::$app->params['pushNotificationMessage']['newSubscriber'];
                $replaceContent=[];   
                $replaceContent['USER'] = Yii::$app->user->identity->username;
                $notificationData['body'] = $modelNotification->replaceContent($notificationData['body'],$replaceContent);   
            
                $userIds=[];
                $userIds[]   =   $subscribeToUserId;
            
                $notificationInput['referenceId'] = $modelSubscriptionPlanSubscriber->id;
                $notificationInput['userIds'] = $userIds;
                $notificationInput['notificationData'] = $notificationData;

                
                $modelNotification->createNotification($notificationInput);
                
                // end send notification 

                $response['message']=Yii::$app->params['apiMessage']['subscription']['planAdded'];
                return $response;
            }
            
        }else{
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;

        }
        
    }

    


    public function actionSubscriberList()
    {
        
        //$userId = Yii::$app->user->identity->id;
        //$userId=3;
        $userId =  (int)Yii::$app->request->queryParams['user_id'];
        $model = new SubscriptionPlanSubscriber();

        
        $modleBlockedUser = new BlockedUser();
        $userIdsBlockedMe = $modleBlockedUser->getUserIdsWhomeBlockMe($userId);

       
        $query = $model->find()
        ->where(['subscribe_to_user_id'=>$userId])
        ->andWhere(['NOT',['subcriber_id'=>$userIdsBlockedMe]])
        ->joinWith(['subscriberDetail'=> function ($query) {
            $query->select(['user.id','user.username','user.email','user.bio','user.description','user.image','user.is_verified','user.country_code','user.phone','user.country','user.city','user.sex','user.dob','user.is_chat_user_online','user.chat_last_time_online','user.location','user.latitude','user.longitude','user.profile_visibility']);
            $query->where(['user.status' => User::STATUS_ACTIVE]);
            
            
        }]);
        
        

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

        
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['subscriber']= $dataProvider;
        return $response;
       
    }
    

    public function actionMySubscriptionList()
    {
        
        $userId = Yii::$app->user->identity->id;
        $model = new SubscriptionPlanSubscriber();
        
        $modleBlockedUser = new BlockedUser();
        $userIdsBlockedMe = $modleBlockedUser->getUserIdsWhomeBlockMe($userId);

       
        $query = $model->find()
        ->where(['subcriber_id'=>$userId])
        ->andWhere(['NOT',['subcriber_id'=>$userIdsBlockedMe]])
        ->joinWith(['subscriptionUserDetail'=> function ($query) {
            $query->select(['user.id','user.username','user.email','user.bio','user.description','user.image','user.is_verified','user.country_code','user.phone','user.country','user.city','user.sex','user.dob','user.is_chat_user_online','user.chat_last_time_online','user.location','user.latitude','user.longitude','user.profile_visibility']);
            $query->where(['user.status' => User::STATUS_ACTIVE]);
            
            
        }]);
        
        

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

        
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['subscription']= $dataProvider;
        return $response;
       
    }
   
   

}


