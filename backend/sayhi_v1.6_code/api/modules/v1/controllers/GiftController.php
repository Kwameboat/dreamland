<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;

use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use api\modules\v1\models\User;
use api\modules\v1\models\Gift;
use api\modules\v1\models\GiftSearch;
use api\modules\v1\models\GiftHistory;
use api\modules\v1\models\GiftHistorySearch;

use api\modules\v1\models\Payment;
use api\modules\v1\models\Notification;
use api\modules\v1\models\GiftTimeline;
use api\modules\v1\models\UserLiveBattle;
use api\modules\v1\models\Setting;



/**
 * Controller API
 *
 
 */
class GiftController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\gift';   
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


    public function actionIndex(){


        $model = new GiftSearch();

        $result = $model->search(Yii::$app->request->queryParams);

        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        
        $response['gift']=$result;
        return $response;

        
    }


    public function actionPopular(){

        $model = new GiftSearch();
        $result = $model->searchPopular(Yii::$app->request->queryParams);
        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        $response['gift']=$result;
        return $response;

        
    }

      
    public function actionSendGift()
    {
        $userId                = Yii::$app->user->identity->id;
        $model                =   new GiftHistory();
        $modelGift               =   new Gift();
       
        $modelUser   =   new User();

        $modelSetting = new Setting();

        $settingResult = $modelSetting->find()->one();
        $commissionOnGift = $settingResult->commission_on_gift;
        



        $resultUser = $modelUser->findOne($userId);
       
        $model->scenario ='sendGift';
        
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }
            
           $giftId =  @(int) $model->gift_id;
           $resultGift     = $modelGift->findOne($giftId);
           
            if(!$resultGift){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
            }
           

            if($resultGift->coin > $resultUser->available_coin){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['gift']['notEnoughBalance'];
                $response['errors']=$errors;
                return $response;
            
            }
            
            $adminCommissionCoin =0;
            if($commissionOnGift>0){
                $adminCommissionCoin = $resultGift->coin/100*$commissionOnGift;
            
            }
            $userGetCoin = $resultGift->coin - $adminCommissionCoin;

            
           
            //resultUser
            $modelGiftHistory                =   new GiftHistory();
            $modelGiftHistory->sender_id        =   $userId;
            $modelGiftHistory->reciever_id      =   $model->reciever_id;
            $modelGiftHistory->gift_id          =   $giftId;
            $modelGiftHistory->coin             =   $userGetCoin;
            $modelGiftHistory->coin_actual      =   $resultGift->coin;
            $sendOnType                         = $model->send_on_type;
            $modelGiftHistory->send_on_type     =  $sendOnType;
            
            $modelGiftHistory->live_call_id =null;
            $modelGiftHistory->post_id =null;
            
            $onTypeString = '';
            
            if($sendOnType == $modelGiftHistory::SEND_TO_TYPE_LIVE){
                $modelGiftHistory->live_call_id     =   $model->live_call_id;
                $onTypeString = 'live call';

            }else if($sendOnType == $modelGiftHistory::SEND_TO_TYPE_PROFILE){
                //$modelGiftHistory->reciever_id     =   /same as its profile id
                $onTypeString = 'profile';

            }else if($sendOnType == $modelGiftHistory::SEND_TO_TYPE_POST){
                $modelGiftHistory->post_id     =   $model->post_id;
                $onTypeString = 'post';
            }

            if($modelGiftHistory->save()){

                $giftHistoryId = $modelGiftHistory->id;

                //for sender 

                $resultUser->available_coin  =  $resultUser->available_coin-$resultGift->coin;
                if($resultUser->save(false)){
                    $modelPayment          = new Payment();
                    $modelPayment->type                 =  Payment::TYPE_COIN;
                    $modelPayment->user_id               =  $userId;
                    $modelPayment->transaction_type     =  Payment::TRANSACTION_TYPE_DEBIT;
                    $modelPayment->payment_type         =  Payment::PAYMENT_TYPE_GIFT;
                    $modelPayment->payment_mode         =  Payment::PAYMENT_MODE_WALLET;
                    $modelPayment->coin                 =  $resultGift->coin;
                    $modelPayment->gift_history_id      =  $modelGiftHistory->id;
                    $modelPayment->save(false);

                }


                //for reciever 
                $resultRecieverUser = $modelUser->findOne($model->reciever_id);

                $resultRecieverUser->available_coin  =  $resultRecieverUser->available_coin+$userGetCoin;
                if($resultRecieverUser->save(false)){
                    $modelPayment          = new Payment();
                    $modelPayment->type                 =  Payment::TYPE_COIN;
                    $modelPayment->user_id              =  $model->reciever_id;
                    $modelPayment->transaction_type     =  Payment::TRANSACTION_TYPE_CREDIT;
                    $modelPayment->payment_type         =  Payment::PAYMENT_TYPE_GIFT;
                    $modelPayment->payment_mode         =  Payment::PAYMENT_MODE_WALLET;
                    $modelPayment->coin                 =  $userGetCoin;
                    $modelPayment->gift_history_id      =  $modelGiftHistory->id;
                    $modelPayment->save(false);

                }


                //admin commission
                if($adminCommissionCoin>0){
                    $settingResult->available_coin  =  $settingResult->available_coin+$adminCommissionCoin;
                    if($settingResult->save(false)){
                        $modelPayment                       = new Payment();
                        $modelPayment->type                 =  Payment::TYPE_COIN;
                        $modelPayment->user_id              =  1; //admin
                        $modelPayment->transaction_type     =  Payment::TRANSACTION_TYPE_CREDIT;
                        $modelPayment->payment_type         =  Payment::PAYMENT_TYPE_GIFT_ADMIN_COMMISSION;
                        $modelPayment->payment_mode         =  Payment::PAYMENT_MODE_WALLET;
                        $modelPayment->coin                 =  $adminCommissionCoin;
                        $modelPayment->gift_history_id      =  $modelGiftHistory->id;
                        $modelPayment->save(false);

                    }
                }



                 // send notification 
                $userIds=[];   
                $userIds[]=$model->reciever_id;

                 if($userIds){

                    
                   

                   
                    
                    $modelNotification = new Notification();
                    $notificationInput = [];
                    $notificationData =  Yii::$app->params['pushNotificationMessage']['giftRecieved'];
                    $replaceContent=[];   
                    $replaceContent['ON_TYPE'] = $onTypeString;
                    $notificationData['body'] = $modelNotification->replaceContent($notificationData['body'],$replaceContent);   
                
                    
                
                    $notificationInput['referenceId'] = $giftHistoryId;
                    $notificationInput['userIds'] = $userIds;
                    $notificationInput['notificationData'] = $notificationData;
                    $modelNotification->createNotification($notificationInput);
                    // end send notification 
                }




                $response['message']=Yii::$app->params['apiMessage']['gift']['sent'];
                return $response; 

            }else{
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;
            }

            
        }

       
        
    }



    public function actionRecievedGift()
    {
        
        
        $model = new GiftHistorySearch();
        $result = $model->search(Yii::$app->request->queryParams);
        $response['message']=Yii::$app->params['apiMessage']['common']['recordFound'];
        $response['gift']=$result;
        return $response; 

        

    }

    public function actionLiveCallGiftRecieved()
    {
        
        $userId                = Yii::$app->user->identity->id;
        //$userId=116;
        $modelGiftHistory      =   new GiftHistory();

        
        $liveCallId = @(int)Yii::$app->request->queryParams['live_call_id'];
        $battleId = @(int)Yii::$app->request->queryParams['battle_id'];
        $giftSummary=[];
        $battleUsers=[];
        $isHostUser=1;
        if($battleId>0){
            $battleUsers=[];
            $modelUserLiveBattle = new UserLiveBattle();
            $resultBattle = $modelUserLiveBattle->findOne($battleId);
            $userGiftSummary = $modelUserLiveBattle->getBettleGiftSummary($battleId,$resultBattle->super_host_user_id);
            $userGiftSummary['isMainHost']=1;
            $battleUsers[]=$userGiftSummary;
            $userGiftSummary = $modelUserLiveBattle->getBettleGiftSummary($battleId,$resultBattle->host_user_id);
            $userGiftSummary['isMainHost']=0;
            $battleUsers[]=$userGiftSummary;
            
            if($userId != $resultBattle->super_host_user_id && $userId != $resultBattle->host_user_id ){
                $isHostUser=0;
            }
           

        }else{

            // indivisual summary for user

            $query = $modelGiftHistory->find()
            ->select(['count(id) AS totalGift', 'SUM(coin) AS totalCoin '])
            ->where(['send_on_type'=>GiftHistory::SEND_TO_TYPE_LIVE])
            ->andWhere(['live_call_id'=>$liveCallId]);

            $query->andWhere(['reciever_id'=>$userId]);

            if($battleId>0){
                $query->andWhere(['battle_id'=>$battleId]);
            }
            
            
            $query->groupBy([ 'reciever_id']);

            $results = $query->asArray()->all();

            $totalGift = 0;
            $totalCoin = 0;
            if(count($results)>0){
                $totalGift = $results[0]['totalGift'];
                $totalCoin = $results[0]['totalCoin'];
            }
            $giftSummary=[
                "totalGift"=>$totalGift,
                "totalCoin"=>(int)$totalCoin
            ];

        }
        
        $model = new GiftHistorySearch();
        if($isHostUser){ // no gif history required for third user whose is no host
            $result = $model->searchLiveCallGift(Yii::$app->request->queryParams);
        }else{
            
            $result= ['items'=>[]];
        }
        

        $response['message']=Yii::$app->params['apiMessage']['common']['recordFound'];
        $response['giftSummary']=$giftSummary;
        $response['battleUser']=$battleUsers;
        $response['gift']=$result;
        return $response; 


    }


    
    public function actionLiveCallGiftTopContributer()
    {
        
        $userId                = Yii::$app->user->identity->id;
        
        $modelGiftHistory      =   new GiftHistory();
        $liveCallId = (int)Yii::$app->request->queryParams['live_call_id'];
        $battleId = (int)Yii::$app->request->queryParams['battle_id'];
      

        $query = $modelGiftHistory->find()
        ->select(['gift_history.id,gift_history.sender_id,gift_history.sender_id,count(gift_history.id) AS totalGift', 'SUM(gift_history.coin) AS totalCoin '])
        ->where(['gift_history.send_on_type'=>GiftHistory::SEND_TO_TYPE_LIVE])
        ->andWhere(['gift_history.live_call_id'=>$liveCallId])
        
        ->joinWith(['senderDetail' => function($query) {
            $query->select(['name','username','email','image','id','is_chat_user_online','chat_last_time_online','location','latitude','longitude']);
        }]);

        $query->andWhere(['gift_history.reciever_id'=>$userId]);


        if($battleId>0){
            $query->andWhere(['battle_id'=>$battleId]);
        }
        
        $query->groupBy([ 'gift_history.sender_id']);
        $query->orderBy([ 'totalCoin' => SORT_DESC]);

        $results = $query->all();


        if($battleId>0){
           

        }else{

            // indivisual summary for user



        }
        
       
        $response['message']=Yii::$app->params['apiMessage']['common']['recordFound'];
        $response['contributer']=$results;
        return $response; 


    }


   public function actionTopGiftReciever(){
        $model = new GiftHistory();
        $days =  date('Y-m-d', strtotime("-30 days"));
        $oneMonthTime = strtotime($days);
        // $giftIds =[];
       return $model->find()
        ->select(['id', 'reciever_id', 'sender_id', 'gift_id',  'SUM(coin) AS coin ', 'send_on_type' , 'live_call_id', 'post_id', 'created_at'])
        ->where(['send_on_type'=>GiftHistory::SEND_TO_TYPE_LIVE])
        ->andWhere(['>','created_at',$oneMonthTime])
        ->groupBy([ 'reciever_id'])
        ->orderBy(['SUM(coin)'=>SORT_DESC])
        ->limit(GiftHistory::SHOW_TOP_GIFT_RECIEVER_LIMIT)
        ->all();
   }

   public function actionSendTimelineGift()
    {
        $userId                = Yii::$app->user->identity->id;
        $model                =   new GiftHistory();
        $modelGift               =   new GiftTimeline();
       
        $modelUser   =   new User();
        $resultUser = $modelUser->findOne($userId);
       
        $model->scenario ='sendTimelineGift';
        
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }
            
           $giftId =  @(int) $model->gift_id;
           $resultGift     = $modelGift->findOne($giftId);
           
            if(!$resultGift){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
            }
           

            if($resultGift->coin > $resultUser->available_coin){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['gift']['notEnoughBalance'];
                $response['errors']=$errors;
                return $response;
            
            }

           
            //resultUser
            $modelGiftHistory                =   new GiftHistory();
            $modelGiftHistory->sender_id        =   $userId;
            $modelGiftHistory->reciever_id      =   $model->reciever_id;
            $modelGiftHistory->gift_id          =   $giftId;
            $modelGiftHistory->coin             =   $resultGift->coin;
            $sendOnType                         = $model->send_on_type;
            $modelGiftHistory->send_on_type     =  $sendOnType;
            
            $modelGiftHistory->live_call_id =null;
            $modelGiftHistory->post_id =null;
            $modelGiftHistory->post_type =$model->post_type;
            $onTypeString = ''; 
            
            if($sendOnType == $modelGiftHistory::SEND_TO_TYPE_LIVE){
                $modelGiftHistory->live_call_id     =   $model->live_call_id;
                $onTypeString = 'live call';

            }else if($sendOnType == $modelGiftHistory::SEND_TO_TYPE_PROFILE){
                //$modelGiftHistory->reciever_id     =   /same as its profile id
                $onTypeString = 'profile';

            }else if($sendOnType == $modelGiftHistory::SEND_TO_TYPE_POST){
                $modelGiftHistory->post_id     =   $model->post_id;
                $onTypeString = 'post';
            }

            if($modelGiftHistory->save()){

                $giftHistoryId = $modelGiftHistory->id;

                //for sender 

                $resultUser->available_coin  =  $resultUser->available_coin-$resultGift->coin;
                if($resultUser->save(false)){
                    $modelPayment          = new Payment();
                    $modelPayment->type                 =  Payment::TYPE_COIN;
                    $modelPayment->user_id               =  $userId;
                    $modelPayment->transaction_type     =  Payment::TRANSACTION_TYPE_DEBIT;
                    $modelPayment->payment_type         =  Payment::PAYMENT_TYPE_GIFT;
                    $modelPayment->payment_mode         =  Payment::PAYMENT_MODE_WALLET;
                    $modelPayment->coin                 =  $resultGift->coin;
                    $modelPayment->gift_history_id      =  $modelGiftHistory->id;
                    
                    $modelPayment->save(false);

                }


                //for reciever 
                $resultRecieverUser = $modelUser->findOne($model->reciever_id);

                $resultRecieverUser->available_coin  =  $resultRecieverUser->available_coin+$resultGift->coin;
                if($resultRecieverUser->save(false)){
                    $modelPayment          = new Payment();
                    $modelPayment->type                 =  Payment::TYPE_COIN;
                    $modelPayment->user_id              =  $model->reciever_id;
                    $modelPayment->transaction_type     =  Payment::TRANSACTION_TYPE_CREDIT;
                    $modelPayment->payment_type         =  Payment::PAYMENT_TYPE_GIFT;
                    $modelPayment->payment_mode         =  Payment::PAYMENT_MODE_WALLET;
                    $modelPayment->coin                 =  $resultGift->coin;
                    $modelPayment->gift_history_id      =  $modelGiftHistory->id;
                    $modelPayment->save(false);

                }

                 // send notification 
                $userIds=[];   
                $userIds[]=$model->reciever_id;

                 if($userIds){

                    
                   

                   
                    
                    $modelNotification = new Notification();
                    $notificationInput = [];
                    $notificationData =  Yii::$app->params['pushNotificationMessage']['timelineGiftRecieved'];
                    $replaceContent=[];   
                    $replaceContent['ON_TYPE'] = $onTypeString;
                    $notificationData['body'] = $modelNotification->replaceContent($notificationData['body'],$replaceContent);   
                
                    
                
                    $notificationInput['referenceId'] = $giftHistoryId;
                    $notificationInput['userIds'] = $userIds;
                    $notificationInput['notificationData'] = $notificationData;
                    $modelNotification->createNotification($notificationInput);
                    // end send notification 
                }




                $response['message']=Yii::$app->params['apiMessage']['giftTimeline']['sent'];
                return $response; 

            }else{
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;
            }

            
        }     
        
    }

    public function actionTimelineGift(){


        $model = new GiftTimeline();

        $query = $model->find()->where(['status'=>GiftTimeline::STATUS_ACTIVE]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);
        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        $response['timelineGift']=$dataProvider;
        return $response;

        
    }

    public function actionTimelineGiftRecieved(){
        $userId     =     @Yii::$app->user->identity->id;
        $params =  Yii::$app->request->queryParams;
        
        $query = GiftHistory::find()
        ->where(['gift_history.reciever_id'=>$userId])
        ->joinWith(['senderDetail' => function($query) {
            $query->select(['name','username','email','image','id','is_chat_user_online','chat_last_time_online','location','latitude','longitude']);
        }])
        ->andWhere(['post_type'=>GiftHistory::POST_TYPE_TIMELINE_GIFT])
        ->orderBy(['gift_history.id'=>SORT_DESC]);
        

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);
        
        $query->andFilterWhere([
            'send_on_type' => @$params['send_on_type'],
            'post_id' => @$params['post_id']
            
        ]);

        
        $response['timeline_gift']=$dataProvider;
        return $response;
    }   

}


