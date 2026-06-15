<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use api\modules\v1\models\Club;
use api\modules\v1\models\ClubCategory;
use api\modules\v1\models\ClubUser;
use api\modules\v1\models\ClubSearch;
use api\modules\v1\models\ClubInvitationRequest;
use api\modules\v1\models\ChatRoom;
use api\modules\v1\models\ChatRoomUser;
use api\modules\v1\models\User;
use api\modules\v1\models\Post;
use api\modules\v1\models\Notification;
use yii\web\UploadedFile;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
class ClubController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\club';   

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


    public function actionCategory()
    {
        $userId    =     Yii::$app->user->identity->id;
        $modelClubCategory  =   new ClubCategory();
        
          
        $results =$modelClubCategory->getMainCategory();
        
        
       
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['category'] = $results;
        
        return $response;
      

    }


    public function actionIndex()
    {
        $userId= Yii::$app->user->identity->id;
        $modelSearch = new ClubSearch();
        $result = $modelSearch->searchClub(Yii::$app->request->queryParams);
        
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['club']=$result;
        return $response; 


    }


    public function actionCreate()
    {
        
        $userId             =     Yii::$app->user->identity->id;
        
        $model              =      new Club();
        $modelClubUser      =   new ClubUser();
        $modelChatRoom      =   new ChatRoom();
        $modelChatRoomUser  =   new ChatRoomUser();
        
        $model->scenario = 'create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');

        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
       
        $model->user_id =  $userId;
        if ($model->save()) {

            $clubId = $model->id;

            $modelClubUser->club_id = $clubId;
            $modelClubUser->user_id = $userId;
            $modelClubUser->is_admin = $modelClubUser::IS_ADMIN_YES;
            $modelClubUser->created_by = $userId;
            $modelClubUser->save();

            if($model->is_chat_room){ // if want to chat group

                $modelChatRoom->club_id         = $clubId;
                $modelChatRoom->title           = $model->name;
                $modelChatRoom->image           = $model->image;
                $modelChatRoom->description     = $model->description;
                $modelChatRoom->type            = $modelChatRoom::TYPE_GROUP;
                if($modelChatRoom->save()){
                    $modelChatRoomUser->room_id =  $modelChatRoom->id;
                    $modelChatRoomUser->user_id =  $userId;
                    $modelChatRoomUser->is_admin =  1;
                    $modelChatRoomUser->save();


                    $modelClub =   Club::find()->where(['id'=>$clubId])->one();

                    $modelClub->chat_room_id = $modelChatRoom->id;
                    $modelClub->save();




                }
                


                

            }
            if($model->privacy_type == Club::PRIVACY_TYPE_PUBLIC ){
                $modelPost = new Post();
                $modelPost->type =  Post::TYPE_NORMAL;
                $modelPost->post_content_type =  Post::CONTENT_TYPE_CLUB;
                $modelPost->content_type_reference_id =  $clubId;
                $modelPost->is_add_to_post =  1;
                $modelPost->save(false);
            }

            $response['message'] = Yii::$app->params['apiMessage']['club']['clubCreated'];
            $response['club_id'] = $clubId;
            
            
        } else {

            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
          

        }

        
        
        return $response;
      
        

    }

    public function actionView($id)
    {
        $userId     =     Yii::$app->user->identity->id;
        $model      =   new Club();
        $modelClubUser  =   new ClubUser();
        $clubResult =$model->find()->where(['club.id'=>$id])
        ->joinWith(['createdByUser' => function($query){
            
            $query->select(['id','username','name','email','bio','description','image','is_verified','country_code','phone','country','city','sex','dob','is_chat_user_online','chat_last_time_online']);
           
        }])
        ->one();
        if($clubResult){
            
            $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
            $response['club']=$clubResult;
            
        }else{
            $response['statusCode']=422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors']=$errors;
        }

      
        
        return $response;
      
        

    }
    

    public function actionUpdate($id)
    {
        $userId    =     Yii::$app->user->identity->id;
        $model  =   new Club();
        $modelClubUser  =   new ClubUser();
        $model =   Club::find()->where(['id'=>$id])->one();
        $canUpdate=false;
        if($model->created_by==$userId){
            $canUpdate=true;
        }else{

            $isAdmin = $modelClubUser->find()->where(['user_id'=>$userId,'is_admin'=>ClubUser::IS_ADMIN_YES,'status'=>ClubUser::STATUS_ACTIVE])->count();
            
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
        $model->scenario = 'update';

        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }

        if($model->save(false)){


             $clubResult =$model->findOne($id);

             if($clubResult->is_chat_room){ // if  chat group
                $modelChatRoom      =   new ChatRoom();
                $resultChatRoom =   $modelChatRoom->find()->where(['club_id'=>$id])->one();
                if($resultChatRoom){


                    $resultChatRoom->title           = $clubResult->name;
                    $resultChatRoom->image           = $clubResult->image;
                    $resultChatRoom->description     = $clubResult->description;
                    //$resultChatRoom->status = ChatRoom::STATUS_DELETED;
                    $resultChatRoom->save();
                }
            }

            $response['message']=Yii::$app->params['apiMessage']['club']['clubUpdated'];
            $response['club']=$clubResult;
            
        }else{
            $response['statusCode']=422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors']=$errors;
        }

      
        
        return $response;
      
        

    }

    public function actionJoin()
    {
        $userId                 = Yii::$app->user->identity->id;
        $model                  =   new Club();
        $modelClubUser          =      new ClubUser();
        
        $model->scenario ='join';
        
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }
            
           $currentTime=time();
           $clubId =  @(int) $model->id;
           $resultClub     = $model->findOne($clubId);
           
            if(!$resultClub){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
            }

            if($resultClub->is_request_based){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['club']['requestBasedClub'];
                $response['errors']=$errors;
                return $response;
            }

    
           
            $resultCountClubUser =$modelClubUser->find()->where(['user_id'=>$userId,'club_id'=>$clubId,'status'=>$modelClubUser::STATUS_ACTIVE])->count();

            if($resultCountClubUser>0){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['club']['alreadyJoinedClub'];
                $response['errors']=$errors;
                return $response;
            
            }


            //resultUser
            $modelClubUser->user_id     =   $userId;
            $modelClubUser->club_id     =   $clubId;

            if($modelClubUser->save()){

                $modelClubInvitationRequest          =      new ClubInvitationRequest();
                $pendingResult =  $modelClubInvitationRequest->find()->where(['club_id'  =>  $clubId,'user_id'  =>  $userId,'status'=>ClubInvitationRequest::STATUS_PENDING])->one();
                if($pendingResult){
                    $pendingResult->status= ClubInvitationRequest::STATUS_CANCELLED;
                    $pendingResult->save();
                }
             


                if($resultClub->is_chat_room){

                    $modelChatRoomUser  =   new ChatRoomUser();
                    $modelChatRoomUser->room_id =  $resultClub->chat_room_id;
                    $modelChatRoomUser->user_id =  $userId;
                    $modelChatRoomUser->save();
                }
                $response['message']=Yii::$app->params['apiMessage']['club']['joinSuccess'];
                return $response; 
            }else{

                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;

            }
        }
    }
    public function actionLeft()
    {
        $userId                 = Yii::$app->user->identity->id;
        $model                  =   new Club();
        $modelClubUser          =      new ClubUser();
        
        $model->scenario ='join';
        
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }
            
           $currentTime=time();
           $clubId =  @(int) $model->id;
           $resultClub     = $model->findOne($clubId);
           
            if(!$resultClub){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
            }

            if($resultClub->user_id == $userId){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['club']['notRemoveClubOwner'];
                $response['errors']=$errors;
                return $response;
            
            }
    
           
            $resultClubUser =$modelClubUser->find()->where(['user_id'=>$userId,'club_id'=>$clubId,'status'=>$modelClubUser::STATUS_ACTIVE])->one();
            
            if(!$resultClubUser){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
            }

            //resultUser
            $resultClubUser->status     =  ClubUser::STATUS_LEFT;
            
            if($resultClubUser->save()){

                if($resultClub->is_chat_room){

                    $modelChatRoomUser  =   new ChatRoomUser();
                    $resultChatRoomUser =$modelChatRoomUser->find()->where(['user_id'=>$userId,'room_id'=>$resultClub->chat_room_id,'status'=>ChatRoomUser::STATUS_ACTIVE])->one();
                    if($resultChatRoomUser){

                        $resultChatRoomUser->status =  ChatRoomUser::STATUS_LEFT;
                        $resultChatRoomUser->save();
                    }

                }
                $response['message']=Yii::$app->params['apiMessage']['club']['leftSuccess'];
                return $response; 
            }else{

                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;

            }
        }
    }


    public function actionRemove()
    {
        $userId                 = Yii::$app->user->identity->id;
        $model                  =   new Club();
        $modelClubUser          =      new ClubUser();
        
        $model->scenario ='remove';
        
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }
            
           $currentTime=time();
           $clubId =  @(int) $model->id;
           $clubUserId =  @(int) $model->club_user_id;
           $resultClub     = $model->findOne($clubId);
           
            if(!$resultClub){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
            }

            if($resultClub->user_id != $userId){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['notAllowed'];
                $response['errors']=$errors;
                return $response;
            
            }
          
    
    
           
            $resultClubUser =$modelClubUser->find()->where(['id'=>$clubUserId,'status'=>$modelClubUser::STATUS_ACTIVE])->one();
            
            if(!$resultClubUser){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
            }

            if($resultClubUser->user_id == $userId){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['club']['notRemoveClubOwner'];
                $response['errors']=$errors;
                return $response;
            
            }

            //resultUser
            $resultClubUser->status     =  ClubUser::STATUS_REMOVED;
            
            if($resultClubUser->save()){

                if($resultClub->is_chat_room){

                    $modelChatRoomUser  =   new ChatRoomUser();
                    $resultChatRoomUser =$modelChatRoomUser->find()->where(['user_id'=>$resultClubUser->user_id,'room_id'=>$resultClub->chat_room_id,'status'=>ChatRoomUser::STATUS_ACTIVE])->one();
                    if($resultChatRoomUser){

                        $resultChatRoomUser->status =  ChatRoomUser::STATUS_REMOVED;
                        $resultChatRoomUser->save();
                    }

                }
                $response['message']=Yii::$app->params['apiMessage']['club']['removedSuccess'];
                return $response; 
            }else{

                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;

            }
        }
    }
    
    public function actionClubJoinedUser($id)
    {
        $userId= Yii::$app->user->identity->id;

        $modelClubUser  = new ClubUser();


        $query  = $modelClubUser->find()->where(['club_user.club_id'=>$id,'club_user.status'=>ClubUser::STATUS_ACTIVE])
        ->joinWith(['user' => function($query){
            $query->select(['id','name','username','email','bio','description','image','country_code','phone','country','city','sex','is_chat_user_online','chat_last_time_online']);
        }]);
        

        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

        
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['userList']=$dataProvider;
        return $response; 


    }

    public function actionDelete($id)
    {
        $userId    =     Yii::$app->user->identity->id;
        $model  =   new Club();
        $model =   Club::find()->where(['id'=>$id])->one();
        
        if($model->user_id!=$userId){
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['notAllowed'];
            $response['errors'] = $errors;
            return $response;
        }
        
        $model->status =  Club::STATUS_DELETED;
        $model->updated_at = time();
        $model->updated_by   =   $userId;

        if($model->save(false)){
            $clubResult =$model->findOne($id);
            if($clubResult->is_chat_room){ // if  chat group
                $modelChatRoom      =   new ChatRoom();
                $resultChatRoom =   $modelChatRoom->find()->where(['club_id'=>$id])->one();
                if($resultChatRoom){
                    $resultChatRoom->status = ChatRoom::STATUS_DELETED;
                    $resultChatRoom->save(false);
                }
            }

            $response['message']=Yii::$app->params['apiMessage']['club']['clubDeleted'];
            //$response['club']=$clubResult;
            
        }else{
            $response['statusCode']=422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors']=$errors;
        }

      
        
        return $response;
      
        

    }


    public function actionInvite()
    {
        $userId                 = Yii::$app->user->identity->id;
        $modelClub                  =   new Club();
        $modelClubInvitationRequest          =      new ClubInvitationRequest();
        
        $modelClubInvitationRequest->scenario ='invite';
        
        if (Yii::$app->request->isPost) {
            $modelClubInvitationRequest->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$modelClubInvitationRequest->validate()) {
                $response['statusCode']=422;
                $response['errors']=$modelClubInvitationRequest->errors;
                return $response;
            }
            
           $currentTime=time();
           $clubId =  @(int) $modelClubInvitationRequest->club_id;
            $userIds =  $modelClubInvitationRequest->user_ids;
           $resultClub     = $modelClub->findOne($clubId);
           
            if(!$resultClub){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
            }

            $userArr = explode(',',$userIds);

            $isSend=false;
            $userNotificationIds=[];
            foreach($userArr as $uId){

                $modelClubInvitationRequestInsert          =      new ClubInvitationRequest();
                
                $isAlreadyAdded =  $modelClubInvitationRequestInsert->find()->where(['club_id'  =>  $clubId,'user_id'  =>  $uId,'type'  =>  ClubInvitationRequest::TYPE_INVITATION])->count();
                if(!$isAlreadyAdded){
                    $modelClubInvitationRequestInsert->club_id  =  $clubId;
                    $modelClubInvitationRequestInsert->user_id  =  $uId;
                    $modelClubInvitationRequestInsert->type     =  ClubInvitationRequest::TYPE_INVITATION;
                    $modelClubInvitationRequestInsert->message  =  $modelClubInvitationRequest->message;
                    if($modelClubInvitationRequestInsert->save()){



                        $isSend=true;

                        $userNotificationIds[]= $uId;

                    }
                }
            }

            if($isSend){

                if($userNotificationIds){

                    
                    $modelNotification = new Notification();
                    $notificationInput = [];
                    $notificationData =  Yii::$app->params['pushNotificationMessage']['clubInvitation'];
                    //$replaceContent=[];   
                   // $replaceContent['TITLE'] = $model->title;
                    //$notificationData['body'] = $modelNotification->replaceContent($notificationData['body'],$replaceContent);   
                
                    
                
                    $notificationInput['referenceId'] = $clubId;
                    $notificationInput['userIds'] = $userNotificationIds;
                    $notificationInput['notificationData'] = $notificationData;
                    $modelNotification->createNotification($notificationInput);
                    // end send notification 
                }

                 $response['message']=Yii::$app->params['apiMessage']['club']['inviteSuccess'];
                 return $response; 
             }else{
 
                 $response['statusCode']=422;
                 $errors['message'][] = Yii::$app->params['apiMessage']['club']['alreadyInvited'];
                 $response['errors']=$errors;
                 return $response;
 
             }
        }
    }

    public function actionMyInvitation()
    {
        $userId= Yii::$app->user->identity->id;
        $modelClubInvitationRequest          =      new ClubInvitationRequest();

        //$modelClubUser  = new ClubUser();


        $query  = $modelClubInvitationRequest->find()->where(['club_invitation_request.user_id'=>$userId,'club_invitation_request.status'  =>  ClubInvitationRequest::STATUS_PENDING,'club_invitation_request.type'  =>  ClubInvitationRequest::TYPE_INVITATION]);
        

        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

        
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['invitation']=$dataProvider;
        return $response; 


    }

    
    public function actionInvitationReply()
    {
        $userId                 = Yii::$app->user->identity->id;
        $modelClub                  =   new Club();
        $modelClubInvitationRequest   =      new ClubInvitationRequest();
        $modelClubUser              =  new ClubUser();
        
        $modelClubInvitationRequest->scenario ='invitationReply';
        
        if (Yii::$app->request->isPost) {
            $modelClubInvitationRequest->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$modelClubInvitationRequest->validate()) {
                $response['statusCode']=422;
                $response['errors']=$modelClubInvitationRequest->errors;
                return $response;
            }
            
           $invitationId =  @(int) $modelClubInvitationRequest->id;
           $newStatus =  $modelClubInvitationRequest->status;
           
           $resultClubInvitationRequest     = $modelClubInvitationRequest->findOne($invitationId);
           
            if(!$resultClubInvitationRequest){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
            }

            $clubId = $resultClubInvitationRequest->club_id;

            if($resultClubInvitationRequest->status!=ClubInvitationRequest::STATUS_PENDING){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionAlready'];
                $response['errors']=$errors;
                return $response;
            
            }

            if($resultClubInvitationRequest->user_id !=$userId || $resultClubInvitationRequest->type !=ClubInvitationRequest::TYPE_INVITATION ){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['notAllowed'];
                $response['errors']=$errors;
                return $response;
            
            }
            $resultClubInvitationRequest->status = $newStatus;

            if($resultClubInvitationRequest->save()){
                $message  = Yii::$app->params['apiMessage']['common']['actionSuccess'];
                if($newStatus==ClubInvitationRequest::STATUS_ACCEPTED){
                    $resultCountClubUser =$modelClubUser->find()->where(['user_id'=>$userId,'club_id'=>$clubId,'status'=>$modelClubUser::STATUS_ACTIVE])->count();

                    if(!$resultCountClubUser){
                        $modelClubUser->user_id     =   $userId;
                        $modelClubUser->club_id     =   $clubId;
                        $modelClubUser->created_by  =   $userId;
            
                        if($modelClubUser->save()){
                            $resultClub = $modelClub->findOne($clubId);
                            if($resultClub->is_chat_room){
                                $modelChatRoomUser  =   new ChatRoomUser();
                                $modelChatRoomUser->room_id =  $resultClub->chat_room_id;
                                $modelChatRoomUser->user_id =  $userId;
                                $modelChatRoomUser->created_by =  $userId;
                                $modelChatRoomUser->save();
                            }
                        }
                    }
                    $message=Yii::$app->params['apiMessage']['club']['joinSuccess'];
                  }
        
                 $response['message']=$message;
                 return $response; 
             }else{
 
                 $response['statusCode']=422;
                 $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                 $response['errors']=$errors;
                 return $response;
 
             }
        }
    }


    public function actionJoinRequest()
    {
        $userId                 = Yii::$app->user->identity->id;
        $modelClub                  =   new Club();
        $modelClubInvitationRequest          =      new ClubInvitationRequest();
        
        $modelClubInvitationRequest->scenario ='joinRequest';
        
        if (Yii::$app->request->isPost) {
            $modelClubInvitationRequest->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$modelClubInvitationRequest->validate()) {
                $response['statusCode']=422;
                $response['errors']=$modelClubInvitationRequest->errors;
                return $response;
            }
            
         
           $clubId =  @(int) $modelClubInvitationRequest->club_id;
           $resultClub     = $modelClub->findOne($clubId);
           
            if(!$resultClub){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
            }
           
            $modelClubInvitationRequestInsert          =      new ClubInvitationRequest();
            
            $isAlreadyRequested =  $modelClubInvitationRequestInsert->find()->where(['club_id'  =>  $clubId,'user_id'  =>  $userId,'type'  =>  ClubInvitationRequest::TYPE_REQUEST,'status'  =>  ClubInvitationRequest::STATUS_PENDING])->count();

            if($isAlreadyRequested){

                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['club']['alreadyJoinRequest'];
                $response['errors']=$errors;
                return $response;


            }
            
            $modelClubInvitationRequestInsert->club_id  =  $clubId;
            $modelClubInvitationRequestInsert->user_id  =  $userId;
            $modelClubInvitationRequestInsert->type     =  ClubInvitationRequest::TYPE_REQUEST;
            $modelClubInvitationRequestInsert->message  =  $modelClubInvitationRequest->message;
            if($modelClubInvitationRequestInsert->save()){
     
                $userNotificationIds=[];
                $userNotificationIds[]=$resultClub->user_id;

                $modelNotification = new Notification();
                $notificationInput = [];
                $notificationData =  Yii::$app->params['pushNotificationMessage']['clubJoinRequest'];
                //$replaceContent=[];   
                // $replaceContent['TITLE'] = $model->title;
                //$notificationData['body'] = $modelNotification->replaceContent($notificationData['body'],$replaceContent);   
            
                $notificationInput['referenceId'] = $clubId;
                $notificationInput['userIds'] = $userNotificationIds;
                $notificationInput['notificationData'] = $notificationData;
                $modelNotification->createNotification($notificationInput);
                // end send notification 
            
                 $response['message']=Yii::$app->params['apiMessage']['club']['joinRequestSend'];
                 return $response; 

            }else{

                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['club']['alreadyInvited'];
                $response['errors']=$errors;
                return $response;

            }
        
        
        }


    }

    public function actionJoinRequestList()
    {
        $userId= Yii::$app->user->identity->id;
        $modelClubInvitationRequest          =      new ClubInvitationRequest();

        
        $clubId = (int)Yii::$app->request->queryParams['club_id'];

        $query  = $modelClubInvitationRequest->find()->where(['club_invitation_request.club_id'=>$clubId,'club_invitation_request.status'  =>  ClubInvitationRequest::STATUS_PENDING,'club_invitation_request.type'  =>  ClubInvitationRequest::TYPE_REQUEST])
        ->joinWith(['user' => function($query){
            $query->select(['id','name','username','email','bio','description','image','country_code','phone','country','city','sex','is_chat_user_online','chat_last_time_online']);
        }]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);
        
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['join_request']=$dataProvider;
        return $response; 


    }


    
    public function actionJoinRequestReply()
    {
        $userId                 = Yii::$app->user->identity->id;
        $modelClub                  =   new Club();
        $modelClubInvitationRequest   =      new ClubInvitationRequest();
        $modelClubUser              =  new ClubUser();
        
        $modelClubInvitationRequest->scenario ='jointRequestReply';
        
        if (Yii::$app->request->isPost) {
            $modelClubInvitationRequest->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$modelClubInvitationRequest->validate()) {
                $response['statusCode']=422;
                $response['errors']=$modelClubInvitationRequest->errors;
                return $response;
            }
            
           $requestId =  @(int) $modelClubInvitationRequest->id;
           $newStatus =  $modelClubInvitationRequest->status;
           
           $resultClubInvitationRequest     = $modelClubInvitationRequest->findOne($requestId);
           
            if(!$resultClubInvitationRequest){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
            }

            $clubId = $resultClubInvitationRequest->club_id;

            $resultClub = $modelClub->findOne($clubId);

            if($resultClubInvitationRequest->status!=ClubInvitationRequest::STATUS_PENDING){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionAlready'];
                $response['errors']=$errors;
                return $response;
            
            }

            if($resultClub->user_id !=$userId || $resultClubInvitationRequest->type !=ClubInvitationRequest::TYPE_REQUEST ){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['notAllowed'];
                $response['errors']=$errors;
                return $response;
            
            }
            $resultClubInvitationRequest->status = $newStatus;

            if($resultClubInvitationRequest->save()){
                $message  = Yii::$app->params['apiMessage']['common']['actionSuccess'];
                if($newStatus==ClubInvitationRequest::STATUS_ACCEPTED){
                    $resultCountClubUser =$modelClubUser->find()->where(['user_id'=>$resultClubInvitationRequest->user_id,'club_id'=>$clubId,'status'=>$modelClubUser::STATUS_ACTIVE])->count();

                    if(!$resultCountClubUser){
                        $modelClubUser->user_id     =   $resultClubInvitationRequest->user_id;
                        $modelClubUser->club_id     =   $clubId;
                        $modelClubUser->created_by  =   $userId;
            
                        if($modelClubUser->save()){
                           
                            if($resultClub->is_chat_room){
                                $modelChatRoomUser  =   new ChatRoomUser();
                                $modelChatRoomUser->room_id =  $resultClub->chat_room_id;
                                $modelChatRoomUser->user_id =  $resultClubInvitationRequest->user_id;
                                $modelChatRoomUser->created_by =  $userId;
                                $modelChatRoomUser->save();
                            }
                        }
                    }
                    $message=Yii::$app->params['apiMessage']['club']['requestAccepted'];
                  }
        
                 $response['message']=$message;
                 return $response; 
             }else{
 
                 $response['statusCode']=422;
                 $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                 $response['errors']=$errors;
                 return $response;
 
             }
        }
    }

    public function actionTopClub() {
        $userId     =     Yii::$app->user->identity->id;
        $model      =   new Club();
        $modelClubUser  =   new ClubUser();

        $type = @(int)Yii::$app->request->queryParams['type'];
        if($type == Club::TYPE_TRENDING_CLUB){
            $days =  date('Y-m-d', strtotime("-7 days"));
            $totalDays = strtotime($days);
        }elseif($type == Club::TYPE_TOP_CLUB){
            $days =  date('Y-m-d', strtotime("-1000 days"));
            $totalDays = strtotime($days);  
        }else{
            $response['statusCode']=422;
                $response['errors']='Type cannot be blank.';
                return $response;
        }

        $clubResult =$model->find()->where(['status'=>Club::STATUS_ACTIVE])->all();
        $clubdata = [];
        if(count($clubResult)>0){
           
            foreach($clubResult as $key=> $clubData){
                $clubId = $clubData['id'];
               $totalPoint = $model->getClubTotalPoint($clubId ,$totalDays);
                if( $totalPoint > Club::IS_TRENDING_MIN_VALUE){
                    $clubdata[$key]['id']  = $clubId;
                    $clubdata[$key]['points'] =   $totalPoint;// $model->getClubTotalPoint($clubId ,$totalDays);
                }
            }
        }

      if(count($clubdata)> 0){           
       
        $points = array_column($clubdata, 'points');
        array_multisort($points, SORT_DESC, $clubdata);

        $clubIds=[];
        foreach($clubdata as $clubvalue){
            $clubIds[]=  $clubvalue['id'];
        }
        $clubIdTostr = implode(', ', $clubIds);
        if($type == Club::TYPE_TOP_CLUB){
            $clubTrendingResult =  $model->find()->where(['IN','id',$clubIds])
            ->orderBy(new Expression("FIELD(`id`,$clubIdTostr)"))
            ->limit(Club::IS_TOP_CLUB_LIMIT)->all();
        }else{
            $clubTrendingResult =  $model->find()->where(['IN','id',$clubIds])
            ->orderBy(new Expression("FIELD(`id`,$clubIdTostr)"))
            ->limit(Club::IS_TRENDING_CLUB_LIMIT)->all();
        }
        
        if($clubTrendingResult){            
            $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
            $response['trendingClub']=$clubTrendingResult;            
        }else{
            $response['statusCode']=422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors']=$errors;
        }
       
      }else{
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['trendingClub']=$clubdata; 
      }
      return $response;
    }


}


