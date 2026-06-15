<?php

namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use api\modules\v1\models\User;
use api\modules\v1\models\UserPreference;
use api\modules\v1\models\UserPreferenceInterest;
use api\modules\v1\models\UserPreferenceLanguage;
use api\modules\v1\models\ProfileCategoryType;
use api\modules\v1\models\Notification;
use api\modules\v1\models\DatingProfileViewAction;
use api\modules\v1\models\DatingMatchProfile;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use api\modules\v1\models\DatingDailyProfileView;
use yii\web\NotFoundHttpException;
use api\modules\v1\models\DatingSubscriptionPackage;
use api\modules\v1\models\Payment;
use api\modules\v1\models\DatingUserSubscription;
use yii\data\ActiveDataProvider;

class DatingController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\UserPreference';   
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


    public function actionIndex(){
        
        $params = \Yii::$app->request->queryParams;
        $countryId=       @$params['country_id'];
        $model =  new UserPreference();

        $modelResult  =$model->getStateList($countryId); 
         $response['message']='Ok';
        $response['state']=$modelResult;
        
        return $response;
    }


    /**
     * Profile user
     */
    public function actionAddUserPreference(){  
        $id = Yii::$app->user->identity->id;
        // $model = $this->findModel($id);
        $model = new UserPreference();
        //  $model->scenario ='create';
        // $model->scenario ='AddUserPreference';

        // $request = Yii::$app->request;
        // $params = $request->bodyParams;
        // $model->attributes=$params;

        // print_r($model);
        $model->user_id = $id;
        // exit;
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
        
        
        if($model->save(false)){
            if (!empty($model->language)) {
                // exit("fh");
                $modelUserLanguage = new UserPreferenceLanguage();
                $modelUserLanguage->updateUserPreferenceLanguage($id, $model->language);
            }else{
                // when Language value is empty then delete old data
                $modelUserLanguage = new UserPreferenceLanguage();
                $modelUserLanguage->deleteUserPreferenceLanguage($id);
            }
            if (!empty($model->interest)) {
                $modelUserInterest = new UserPreferenceInterest();
                $modelUserInterest->updateUserPreferenceInterest($id, $model->interest);
            }else{
                // when interest value is empty then delete old data
                $modelUserInterest = new UserPreferenceInterest();
                $modelUserInterest->deleteUserPreferenceInterest($id);
            }
          //  $modelUserLocation =  new UserLocation();
            //$modelUserLocation->updateUserLocation($id,$params['locations']);
            
            $response['message']='User Preference Updated successfully';
            return $response; 

        }
        
    }

        /**
     * my Preference Profile
     *      */
    public function actionPreferenceProfile(){
       
        $model =  new UserPreference();
        // $modelPackage =  new Package();
        $id = Yii::$app->user->identity->id;
        $userProfile = $model->getPreferenceProfile($id);
        // print_r($userProfile);
        // $response['user']= $userProfile;
        $response['preferenceSetting']=   $userProfile; 
        return $response; 

        
    }

    public function actionPreferenceProfileMatch(){
       
        $model =  new UserPreference();
        $id = Yii::$app->user->identity->id;
        $limit=0;
        $modelUserSubscription = new DatingUserSubscription();
        $totalProfileShow =  $modelUserSubscription->getProfileLimitBySubscriptionPackage($id); // get profile by dating package limit
        if(!empty($totalProfileShow)){
            $limit = $totalProfileShow;
        }else{
            $limit = 20;
        }

        $todayDate = date('Y-m-d');
       $currentDate =  strtotime($todayDate);
       $dailyProfileViewModel = new DatingDailyProfileView();
       $modelDailyProfileViewAction = new DatingProfileViewAction();
        $totalProfileView = $modelDailyProfileViewAction->getDailyProfilesView();
        if($totalProfileView < $limit){
         $matchProfile = $model->getPreferenceMatchProfiles($id,$limit);
        }
        else{
            $matchProfile = [];//"You have done daily profile view limit!";
        }
        $response['preferenceMatchProfile']=   $matchProfile; 
        return $response; 
      
    }

    protected function findModel($id)
    {
        if (($model = UserPreference::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * like profile
     */
    public function actionProfileActionLike(){
        $model = new DatingProfileViewAction();
        // $modelFollower = new Follower();
        $userId = Yii::$app->user->identity->id;
        $model->scenario = 'create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        $profile_user_id = @(int) $model->profile_user_id;
        // $type = @(int) $model->type;
        $type = 1;
        $model->type = $type;
         // print_r( $model);
        // exit;
        $totalCount = $model->find()->where(['profile_user_id' => $profile_user_id, 'user_id' => $userId])->count();
       
        if ($totalCount > 0) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['userPreference']['userPreferenceLikeAlready'];
            $response['errors'] = $errors;
            return $response;

        }

        if ($model->save(false)) {
            $modelPost = new DatingProfileViewAction();
            $totalLike = $modelPost->updateMatchProfile($profile_user_id , $userId ,$type );
           
            $modelNotification = new Notification();
            $notificationInput = [];
            $notificationData =  Yii::$app->params['pushNotificationMessage']['likeUserProfile'];
            $replaceContent=[];   
            $replaceContent['USER'] = Yii::$app->user->identity->username;
            $notificationData['body'] = $modelNotification->replaceContent($notificationData['body'],$replaceContent);   
           
            $userIds=[];
            $userIds[]   =  $profile_user_id; //$resultPost->user_id; 
           
            $notificationInput['referenceId'] = $userId;
            $notificationInput['userIds'] = $userIds;
            $notificationInput['notificationData'] = $notificationData;
            // $notificationInput['isFollowing'] = $isFollowing;
            // print_r($notificationInput);
            // exit("dj");
            $modelNotification->createNotification($notificationInput);
            // end send notification 
            $response['message'] = Yii::$app->params['apiMessage']['userPreference']['userPreferenceLikeSuccess'];
            $response['like'] = $totalLike;
            return $response;
       
        } else {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['userPreference']['userPreferenceLikeFailed'];
            $response['errors'] = $errors;
            return $response;
        }
    }

    public function actionProfileActionSkip(){
        $model = new DatingProfileViewAction();
        // $modelFollower = new Follower();
        $userId = Yii::$app->user->identity->id;
        $model->scenario = 'create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        $profile_user_id = @(int) $model->profile_user_id;
        // $type = @(int) $model->type;
        $type = 2;
        $model->type = $type;
       
        
       $totalCount = $model->find()->where(['profile_user_id' => $profile_user_id, 'user_id' => $userId])->count();
       
        if ($totalCount > 0) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['userPreference']['userPreferenceSkipAlready'];
            $response['errors'] = $errors;
            return $response;

        }

        if ($model->save(false)) {

            $response['message'] = Yii::$app->params['apiMessage']['userPreference']['userPreferenceskip'];
            // $response['total_like'] = $totalLike;
            return $response;
        } else {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['userPreference']['userPreferenceSkipFailed'];
            $response['errors'] = $errors;
            return $response;
        }
    }
    // Get All matching profile list likes by both users
    public function actionProfileMatching(){
       
        $model =  new DatingMatchProfile();
        $id = Yii::$app->user->identity->id;
        $limit=20;
        $query = $model->getMatchProfilesByUser($id,$limit);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

        
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['userMatch']=   $dataProvider; 
        return $response; 

        
    }
    // login users all likes profile lists
    public function actionProfileLikeLists(){
       
        $model =  new DatingProfileViewAction();
        $id = Yii::$app->user->identity->id;
        $limit=20;
        $matchProfile = $model->getLikeProfilesByUser($id,$limit);
        $response['userMatch']=   $matchProfile; 
        return $response; 

        
    }


    public function actionProfileActionRemove(){
        $model = new DatingProfileViewAction();
        $userId = Yii::$app->user->identity->id;
        $model->scenario = 'create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        $profile_user_id = @(int) $model->profile_user_id;
       
        
       $totalCount = $model->find()->where(['profile_user_id' => $profile_user_id, 'user_id' => $userId , 'type' => 1])->count();
        if ($totalCount > 0) {

            $model->getRemoveProfileFromViewAction($userId , $profile_user_id);
            $response['message'] = Yii::$app->params['apiMessage']['userPreference']['userPreferenceRemoveLike'];
            return $response;

        }else{
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['userPreference']['userPreferenceRemoveFailed'];
            $response['errors'] = $errors;
            return $response;
        }
    }

    // current login user profile like by other users
    public function actionProfileLikeByOtherUsers(){
    
        $model =  new DatingProfileViewAction();
        $id = Yii::$app->user->identity->id;
        $limit=20;
        $matchProfile = $model->getLikeCurrentProfileByOtherUsers($id,$limit);
        $response['profileLikeByOtherUsers']=   $matchProfile; 
        return $response; 

        
    }
    
    public function actionSubscriptionPackage(){
        $model =  new DatingSubscriptionPackage();
        $results = $model->getSubscriptionPackage();
       
        $response['message']='ok';
        $response['datingPackage']=$results;
        
        return $response;
    }

    public function actionSubscribePackage(){
        $model          = new Payment();
        $modelPackage   = new DatingSubscriptionPackage();
        $modelUserSubscription = new DatingUserSubscription();
        $userId = Yii::$app->user->identity->id;
        $model->scenario ='datingSubscription';

        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
        
            return $response;
        }
        $packageId =  @(int) $model->dating_subscription_id;
        $packageResult =$modelPackage->findOne($packageId);
        if(empty($packageResult)){
            $response['statusCode']=422;
            $response['message']='Dating package not found';
            return $response; 
        }
        // print_r($packageResult);
        // echo $packageSubcriptionDuration;
        // exit;
        $packageCoin = @$packageResult->coin; //Coin need to subscribe dating package
        $packageSubcriptionDuration =  @$packageResult->duration;
        $packageDurationTime ='';
        $currentTime =  strtotime("now");
        if(!empty($packageSubcriptionDuration)){
            if($packageSubcriptionDuration == DatingSubscriptionPackage::DATING_SUBSCRIPTION_ONE_WEEK){
                $packageDurationTime =  strtotime("+1 week");
            }elseif($packageSubcriptionDuration == DatingSubscriptionPackage::DATING_SUBSCRIPTION_ONE_MONTH){            
                $packageDurationTime = strtotime("+1 month", $currentTime);               
            }
            elseif($packageSubcriptionDuration == DatingSubscriptionPackage::DATING_SUBSCRIPTION_THREE_MONTH){
                $packageDurationTime = strtotime("+3 month", $currentTime); 
            }
            elseif($packageSubcriptionDuration == DatingSubscriptionPackage::DATING_SUBSCRIPTION_SIX_MONTH){
                $packageDurationTime = strtotime("+6 month", $currentTime); 
            }
            elseif($packageSubcriptionDuration == DatingSubscriptionPackage::DATING_SUBSCRIPTION_ONE_YEAR){
                $packageDurationTime = strtotime("+ 1 year", $currentTime); 
            }
           
        }

        $modelUser =  User::findOne($userId);
        $totalCoinInUserAccount = $modelUser->available_coin;
        if(!empty($packageCoin > $totalCoinInUserAccount)){
            $response['statusCode']=422;
            $response['message']='Insufficient coins';
            return $response; 
        }
        // die("du");
        $modelUser->available_coin =  $modelUser->available_coin - $packageCoin; 
                
        if($modelUser->save(false)){



            $model->type                 =  Payment::TYPE_COIN;
            $model->transaction_type     =  Payment::TRANSACTION_TYPE_CREDIT;
            $model->payment_type         =  Payment::PAYMENT_TYPE_DATING_SUBSCRIPTION;
            $model->payment_mode         =  Payment::PAYMENT_MODE_DATING_SUBSCRIPTION_PURCHASE;
            $model->coin                 =  $packageCoin;
            // $amount = $model->amount;
            // unset($model->amount);
            

            if($model->save(false)){
                $modelUserSubscription = new DatingUserSubscription();
                $modelUserSubscription->dating_subscription_id = $packageId;
                $modelUserSubscription->user_id       =   $userId;
                $modelUserSubscription->start_date    =  $currentTime;
                $modelUserSubscription->expiry_date   =  $packageDurationTime;
                $modelUserSubscription->status        =  DatingUserSubscription::STATUS_ACTIVE;
                $modelUserSubscription->created_at    = $currentTime;
                $modelUserSubscription->save(false);

                $response['message']='Dating Package subscribed successfully';
                return $response; 
            }
        }else{
            $response['statusCode']=422;
            $response['message']='Dating Package not subscribed successfully';
            return $response; 

        }
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
}


