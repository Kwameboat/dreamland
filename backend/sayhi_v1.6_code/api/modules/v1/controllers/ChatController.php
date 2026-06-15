<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use api\modules\v1\models\ChatRoom;
use api\modules\v1\models\ChatMessage;
use api\modules\v1\models\ChatMessageUser;
use api\modules\v1\models\ChatRoomUser;
use api\modules\v1\models\CallHistory;
use api\modules\v1\models\User;
use api\modules\v1\models\UserLiveHistory;
use api\modules\v1\models\LiveCallViewer;
use api\modules\v1\models\Post;
use yii\web\UploadedFile;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
class ChatController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\chatRoom';   

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



    public function actionCreateRoom()
    {
        
        $userId    =     Yii::$app->user->identity->id;
        
        $model  =   new ChatRoom();
        $modelChatRoomUser  =   new ChatRoomUser();

        
        $model->scenario = 'createRoom';

        $model->load(Yii::$app->getRequest()->getBodyParams(), '');

        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }


        if($model->type == ChatRoom::TYPE_OPEN_GROUP){
            $totalCount = (int)$model->find()->where(['status'=>ChatRoom::STATUS_ACTIVE,'title'=>$model->title])->count();
            if($totalCount>0){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['chat']['roomNameAlready'];
                $response['errors']=$errors;
                return $response;

            }
        }

        $roomId= 0;
        if($model->type == ChatRoom::TYPE_PERSONAL){
            $receiverId =$model->receiver_id;
            $query =$model->find()
            ->joinWith(['chatRoomUser'])
            ->where(['chat_room.type'=>ChatRoom::TYPE_PERSONAL])
            ->andWhere(['chat_room_user.user_id'=>$userId]);
            $results = $query->all();
          //  print_r($results);
            
            foreach($results as $result ) {
               // print_r($result->chatRoomUser);

                $roomId= 0;


               //#  //end check my room delete/or not  and action if deleted

                //start check  room delete/or not by reciever  and action if deleted 

                $isUserExist=$modelChatRoomUser->getIsUserInRoom($result->chatRoomUser,$receiverId);
               
                if($isUserExist){
                    
                    if($isUserExist->status!=ChatRoomUser::STATUS_ACTIVE){
                        $isUserExist->status=ChatRoomUser::STATUS_ACTIVE;
                        $isUserExist->save();
                    }


                    $roomId= $result->id;
                     
                    //start check my room delete/or not  and action if deleted
                    $myUserInRoom=$modelChatRoomUser->getMyUserInRoom($result->chatRoomUser,$roomId,$userId);
                    if($myUserInRoom){
                        if($myUserInRoom->status!=ChatRoomUser::STATUS_ACTIVE){
                            $myUserInRoom->status=ChatRoomUser::STATUS_ACTIVE;
                            $myUserInRoom->save();
                        }
    
                        
                    }
                    //END check my room delete/or not  and action if deleted






                    break;
               }
            }
           if($roomId==0){
                if($model->save(false)){
                    $roomId = $model->id;
                    
                   /* $modelChatRoomUser->room_id = $roomId;
                    $modelChatRoomUser->user_id =  $userId;
                    $modelChatRoomUser->save();
                    */
                    
                    
                }
    
            }
            
           // echo  'ROOM: '.$roomId;




          //  print_r($result);



        }else if($model->type == ChatRoom::TYPE_GROUP || $model->type == ChatRoom::TYPE_OPEN_GROUP){

            if($model->save(false)){
                $roomId = $model->id;
                /*$modelChatRoomUser->room_id = $roomId;
                $modelChatRoomUser->user_id =  $userId;
                $modelChatRoomUser->save();*/
                
                
            }

        }

        
        //if($model->save(false)){
         
            

        
        if($roomId){

            $roomResult =$model->findOne($roomId);

            $response['message']=Yii::$app->params['apiMessage']['chat']['roomCreated'];
            $response['room_id'] = $roomId;
            $response['room']=$roomResult;
            
        }else{
            $response['statusCode']=422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors']=$errors;
        }
        
        return $response;
      
        

    }

    public function actionChatMessage()
    {
        $userId    =     Yii::$app->user->identity->id;
        
        $model = new \yii\base\DynamicModel([
            'room_id', 'last_message_id',
             ]);
        $model->addRule(['room_id','last_message_id'], 'required');
        
        $model->load(Yii::$app->request->queryParams, '');
        $model->validate();
        if ($model->hasErrors()) {
            
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            
        }
        

        $roomId                 =    (int)$model->room_id;
        $lastMessageId         =    (int)$model->last_message_id;

       
        $modelChatMessage  =   new ChatMessage();
        
  
        $query =$modelChatMessage->find()
        ->where(['chat_message.room_id'=>$roomId]);
        if($lastMessageId>0){
            $query->andWhere(['<','chat_message.id',$lastMessageId]);
        }
            
        $query->joinWith(['chatMessageUser' => function($query){
           // $query->select(['id','username','email','image','is_chat_user_online','chat_last_time_online']);
             $query->where(['<>','chat_message_user.status',ChatRoomUser::STATUS_DELETED]);

        }])
        ->joinWith(['user' => function($query){
             $query->select(['id','username','email','unique_id','image','is_chat_user_online','chat_last_time_online','is_show_online_chat_status']);
              
 
         }])
        ->andWhere(['chat_message_user.user_id'=>$userId])
        ->orderBy(['chat_message.id'=>SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 100,
            ]
        ]);

        
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['chatMessage']= $dataProvider;
        return $response;
        
        
       
      

    }


    

    public function actionUpdateRoom($id)
    {
        
        $userId    =     Yii::$app->user->identity->id;
        
        $model  =   new ChatRoom();
        $modelChatRoomUser  =   new ChatRoomUser();

        
      

        $model =   ChatRoom::find()->where(['id'=>$id])->one();

        //print_r($model);

        
        $canUpdate=false;

        if($model->created_by==$userId){
            $canUpdate=true;
        }else{

            $isAdmin = $modelChatRoomUser->find()->where(['user_id'=>$userId,'access_group'=>ChatRoom::ACCESS_GROUP_ADMIN,'status'=>ChatRoomUser::STATUS_ACTIVE])->count();
            
            if($isAdmin){
                $canUpdate=true;
            }
            
        }
        
        if(!$canUpdate){
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['notAllowed'];
            $response['errors'] = $errors;
            return $response;

        }

       
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');


        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }

        if($model->save(false)){
             $roomResult =$model->findOne($id);
            $response['message']=Yii::$app->params['apiMessage']['chat']['roomUpdated'];
            $response['room']=$roomResult;
            
        }else{
            $response['statusCode']=422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors']=$errors;
        }

      
        
        return $response;
      
        

    }

    public function actionRoom()
    {
        $userId    =     Yii::$app->user->identity->id;
        $model  =   new ChatRoom();
        $modelChatRoomUser  =   new ChatRoomUser();

        
          
        $query =$model->find()->where(['chat_room.status'=>ChatRoom::STATUS_ACTIVE])
        ->joinWith(['chatRoomUser'])
        ->joinWith(['lastMessage'])
        ->joinWith(['chatRoomUser.user' => function($query){
            $query->select(['id','username','email','unique_id','image','is_chat_user_online','chat_last_time_online','is_show_online_chat_status']);

        }])
        ->joinWith(['createdByUser' => function($query){
            $query->select(['id','username','email','unique_id','image','is_chat_user_online','chat_last_time_online','is_show_online_chat_status']);
        }])
        ->andwhere(['chat_room_user.status'=>ChatRoomUser::STATUS_ACTIVE,'chat_room_user.user_id'=>$userId])
        ->orderBy(['chat_message.created_at'=>SORT_DESC]);
        $results = $query->all();
        
        
       
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['room'] = $results;
        
        return $response;
      

    }

    public function actionOpenRoom()
    {
        $userId    =     Yii::$app->user->identity->id;
        $model  =   new ChatRoom();
        $modelChatRoomUser  =   new ChatRoomUser();

        
          
        $query =$model->find()->where(['chat_room.status'=>ChatRoom::STATUS_ACTIVE,'chat_room.type'=>ChatRoom::TYPE_OPEN_GROUP])
        ->joinWith(['chatRoomUser'])
        ->joinWith(['lastMessage'])
        ->joinWith(['chatRoomUser.user' => function($query){
            $query->select(['id','username','email','unique_id','image','is_chat_user_online','chat_last_time_online','is_show_online_chat_status']);

        }])
        ->joinWith(['createdByUser' => function($query){
            $query->select(['id','username','email','unique_id','image','is_chat_user_online','chat_last_time_online','is_show_online_chat_status']);
        }])
        ->groupBy('chat_room.id')
        ->orderBy(new Expression('rand()'));
       
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);
        
       
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['room'] = $dataProvider;
        
        return $response;
      

    }

    public function actionRoomDetail($room_id)
    {
        $id=(int)$room_id;

        
        $userId    =     Yii::$app->user->identity->id;
        $model  =   new ChatRoom();
        $modelChatRoomUser  =   new ChatRoomUser();

        
          
        $results =$model->find()->where(['chat_room.status'=>ChatRoom::STATUS_ACTIVE])
        ->joinWith(['chatRoomUser'])
        ->joinWith(['lastMessage'])
        ->joinWith(['chatRoomUser.user' => function($query){
            $query->select(['id','username','email','unique_id','image','is_chat_user_online','chat_last_time_online','is_show_online_chat_status']);
            

        }])
        ->joinWith(['createdByUser' => function($query){
            $query->select(['id','username','email','unique_id','image','is_chat_user_online','chat_last_time_online','is_show_online_chat_status']);
             

        }])
       
        ->where(['chat_room.id'=>$id])->one();
        
         
        
        
       
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['room'] = $results;
        
        return $response;
      

    }

    public function actionCallDetail($call_id)
    {
        $callId=(int)$call_id;

        
        $userId    =     Yii::$app->user->identity->id;
        $model  =   new CallHistory();
        $results =$model->find()       
        ->where(['call_detail.id'=>$callId])
        ->joinWith(['callerDetail' => function($query){
            $query->select(['id','username','email','unique_id','image','is_chat_user_online','chat_last_time_online','is_show_online_chat_status']);

        }])
        ->joinWith(['receiverDetail' => function($query){
            $query->select(['id','username','email','unique_id','image','is_chat_user_online','chat_last_time_online','is_show_online_chat_status']);

        }])
        ->one();
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['call'] = $results;
        return $response;
    }


    public function actionDeleteRoom()
    {
        $userId    =     Yii::$app->user->identity->id;
        $model  =   new ChatRoom();
        $modelChatRoomUser  =   new ChatRoomUser();

        //print_r($id);

         $roomId=@(int)Yii::$app->getRequest()->queryParams['room_id'];

        if($roomId){

            $roomUser = $modelChatRoomUser->find()->where(['room_id'=>$roomId,'user_id'=>$userId])->one();
            
            $roomUser->status=ChatRoomUser::STATUS_DELETED;
            $roomUser->save();

        }
        
        //request->queryParams;
       
        $response['message']=Yii::$app->params['apiMessage']['chat']['roomDeleted'];
       // $response['room'] = $results;
        
        return $response;
      

    }



    // call history

    public function actionCallHistory()
    {
        $userId    =     Yii::$app->user->identity->id;
        $model  =   new CallHistory();
        
  
        $query =$model->find()
        ->where(
            ['or',
                
                ['call_detail.caller_id'=>$userId],
                ['call_detail.receiver_id'=>$userId]
                
            ])
        ->joinWith(['callerDetail' => function($query){
            $query->select(['id','username','email','unique_id','image','is_chat_user_online','chat_last_time_online','is_show_online_chat_status']);

        }])
        ->joinWith(['receiverDetail' => function($query){
            $query->select(['id','username','email','unique_id','image','is_chat_user_online','chat_last_time_online','is_show_online_chat_status']);

        }])

        ->orderBy(['call_detail.id'=>SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

        
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['callHistory']= $dataProvider;
        return $response;
        
        
       
      

    }


    
    public function actionUploadMediaFile()
    {
        
       

        $model = new \yii\base\DynamicModel([
            'mediaFile'
        ]);
        $model->addRule(['mediaFile'], 'required')
            ->addRule(['mediaFile'], 'file');

        
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            $model->mediaFile = UploadedFile::getInstanceByName('mediaFile'); 
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }

            if($model->mediaFile){
                    
                $microtime 			= 	(microtime(true)*10000);
                $uniqueimage		=	$microtime.'_'.date("Ymd_His").'_'.substr(md5($microtime),0,10); 
                $imageName 			=	$uniqueimage;
                $mediaFileName 		= 	$imageName.'.'.$model->mediaFile->extension; 
                $imagePath 			=	Yii::$app->params['pathUploadChatMedia'] ."/".$mediaFileName;
                $model->mediaFile->saveAs($imagePath,false);
                
            }
            $response['message']=Yii::$app->params['apiMessage']['chat']['fileUploaded'];
            $response['image']=Yii::getAlias('@siteUrl').Yii::$app->urlManagerFrontend->baseUrl.'/uploads/chat/'.$mediaFileName;
            return $response; 
        }
    }   

    

    // live  user 

    public function actionLiveUser()
    {
        $userId    =     Yii::$app->user->identity->id;
        $modelUser  =   new User();

        $query = $modelUser->find()
        
        ->select(['user.id','user.name','user.username','user.email','user.unique_id','user.bio','user.description','user.image','user.is_verified','user.country_code','user.phone','user.country','user.city','user.sex','user.dob','user.is_chat_user_online','user.chat_last_time_online','user.is_show_online_chat_status'])
         ->where(['user.role'=>User::ROLE_CUSTOMER])
         ->andwhere(['user.status'=>User::STATUS_ACTIVE])
         ->andWhere(['<>','user.id',$userId])
         ->andwhere(['user.is_chat_user_online'=>User::COMMON_YES])
         ->joinWith(['userLiveDetail']);
         


        //$user = $query->all();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

    
        
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['user']=$dataProvider;
        return $response; 
      

    }


    // online  user 

    public function actionOnlineUser()
    {
        $userId    =     Yii::$app->user->identity->id;
        $modelUser  =   new User();

        $queryParams= Yii::$app->request->queryParams;
        $profileCategoryType  =  (int)@$queryParams['profile_category_type'];

        $query = $modelUser->find()
        //->select(['user.id','user.name','user.username','user.email','user.description','user.phone','user.image'])
        ->select(['user.id','user.name','user.username','user.email','user.unique_id','user.bio','user.description','user.image','user.is_verified','user.country_code','user.phone','user.country','user.city','user.sex','user.dob','user.is_chat_user_online','user.chat_last_time_online','user.profile_category_type','user.is_show_online_chat_status'])
        ->where(['user.role'=>User::ROLE_CUSTOMER])
        ->andwhere(['user.status'=>User::STATUS_ACTIVE])
        ->andwhere(['user.is_chat_user_online'=>User::COMMON_YES])
        ->andWhere(['<>','user.id',$userId])
        ->limit(50)
        ->orderBy(new Expression('rand()'));
        if($profileCategoryType>0){
            $query->andwhere(['user.profile_category_type'=>$profileCategoryType]);
        }


        $user = $query->all();


        
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['user']=$user;
        return $response; 
    

    }


    
    public function actionDeleteRoomChat()
    {
        
        $userId    =     Yii::$app->user->identity->id;
        
       // $model  =   new ChatRoom();
        $modelChatMessageUser  =   new ChatMessageUser();

        $model = new \yii\base\DynamicModel([
            'room_id',
             ]);
        $model->addRule(['room_id'], 'required');
        
        
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        $model->validate();
        if ($model->hasErrors()) {
            
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            
        }

        $roomId = $model->room_id;

        $modelChatMessage  =   new ChatMessage();
        
  
        $query =$modelChatMessage->find()
        ->select(['chat_message.id'])
        ->where(['chat_message.room_id'=>$roomId]);
        
        $result  =$query->asArray()->all();

        $messageIds=[];
        foreach($result as $result){
            $messageIds[]=$result['id'];
        }



       $modelChatMessageUser->updateAll(['status'=>ChatMessageUser::STATUS_DELETED],['chat_message_id'=>$messageIds,'user_id'=>$userId]);
       
       $response['message']=Yii::$app->params['apiMessage']['chat']['roomChatDeleted'];
       return $response;
      
        

    }


   
   
    public function actionLiveStreamingUser(){
        $userId= @Yii::$app->user->identity->id;
        $model = new UserLiveHistory();
        $parms =  @Yii::$app->request->queryParams;
        $name =  @Yii::$app->request->queryParams['name'];
        $is_following = @(int) Yii::$app->request->queryParams['is_following'];
        $latitude = @$parms['latitude'];
        $longitude = @$parms['longitude'];
        $isNearBy = @$parms['is_near_by'];
        $isBattle = @$parms['is_battle'];
        $isNewUser = @$parms['is_new_user'];
        $isTrending = @$parms['is_trending'];

        $profile_category_type = @(int) Yii::$app->request->queryParams['profile_category_type'];

        $query =$model->find()
        ->select(['user_live_history.id','user_live_history.user_id','user_live_history.status','user_live_history.start_time','user_live_history.end_time','user_live_history.total_time','user_live_history.channel_name','user_live_history.token','user_live_history.total_comment'])
        ->where(['user_live_history.status'=>UserLiveHistory::STATUS_ONGOING]);
        $query->joinWith(['user']); 
        if(!empty($name)){
            
            $query->andWhere(['like','user.name',$name]);
        }
        if(!empty($profile_category_type)){
            $query->andWhere(['user.profile_category_type'=>$profile_category_type]);
        }
        if(!empty($is_following)){            
            $query->innerJoinWith('follower'); 
            $query->andWhere(['follower.follower_id'=>$userId]);
        }
        if($isNewUser){
            $query->andWhere(['>=', 'created_at', strtotime('-30 days')]);
            $query->orderBy('user.id desc');
        }
        if($isTrending){
            $trendingPoint=UserLiveHistory::TOTAL_POINT_FOR_TENDING;

            $query->joinWith('liveCallViewer');
            $query->joinWith('recievedGift');
            $query->addSelect(['live_viewer_count' => 'COUNT(DISTINCT live_call_viewer.id)']);
            $query->addSelect(['total_gift_count' => 'COUNT(DISTINCT  gift_history.id)']);
            $query->addSelect(['total_point' => '(COUNT(DISTINCT live_call_viewer.id) * '.UserLiveHistory::POINT_NEW_USER_JOIN.') + (COUNT(DISTINCT gift_history.id) * '.UserLiveHistory::POINT_NEW_GIFT .') + (total_comment * '.UserLiveHistory::POINT_NEW_COMMENT .' )']);
            $query->groupBy('user_live_history.id');
            $query->orderBy('total_point desc');
            $query->having(['>', 'total_point', $trendingPoint ]);
            
        }

        if($isNearBy){
            $adDisplayRadius=UserLiveHistory::MAXIMUM_RADIUS_FOR_NEAR_BY;
            if($latitude && $longitude){
                $query->addSelect('(
                        3959 * acos (
                        cos ( radians('.$latitude.') )
                        * cos( radians( user.latitude ) )
                        * cos( radians( user.longitude ) - radians('.$longitude.') )
                        + sin ( radians('.$latitude.') )
                        * sin( radians( user.latitude ) )
                    )
                ) AS distance');
                $query->having(['<', 'distance', $adDisplayRadius ]);
                $query->orderBy(['distance'=>SORT_ASC]);
                
                $query->andwhere(['not', ['user.latitude' => null]]);
                $query->andwhere(['not', ['user.longitude' => null]]);
            }
        }
        
        if($isBattle){
            $query->innerJoinWith('battle'); 
        }
        $query->distinct();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['liveStreamUser']= $dataProvider;
        return $response;
        
    }

    public function actionLiveCallViewer(){
        $model = new LiveCallViewer();
        $liveCallId =  @(int)Yii::$app->request->queryParams['live_call_id'];
        $role =  @(int)Yii::$app->request->queryParams['role'];
        $isBan =  @(int)Yii::$app->request->queryParams['is_ban'];
        $query =$model->find()->where(['live_call_id'=>$liveCallId])
        ->joinWith(['user' => function($query){
            $query->select(['id','username','email','unique_id','image','is_chat_user_online','chat_last_time_online','user.is_show_online_chat_status']);

        }]);
        if($role>0){
            $query->andWhere(['live_call_viewer.role'=>$role]);
        }
        if($isBan>0){
            $query->andWhere(['is_ban'=>$isBan]);
        }else{
            $query->andWhere(['is_ban'=>0]);
        }
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['live_user_view']= $dataProvider;
        return $response;
       
    }


    

}


