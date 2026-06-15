<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use api\modules\v1\models\RelationShip;
use api\modules\v1\models\RelationInvitationRequest;
use api\modules\v1\models\User;
use api\modules\v1\models\Notification;;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\base\ErrorException;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use api\modules\v1\models\UserSetting;
use api\modules\v1\models\Follower;

class RelationController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\SupportRequest';   
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
            'authMethods' => [
                HttpBearerAuth::className()
            ],
        ];
        return $behaviors;
    } 
    public function actionIndex(){
        $model =  new RelationShip();
        $modelResult  =$model->getList(); 
         $response['message']='Ok';
        $response['relations']=$modelResult;        
        return $response;
    }

    public function actionInvite()
    {
        $loginUserId = Yii::$app->user->identity->id;
        $modelRelation = new RelationShip();
        $modelRelationInvitationRequest = new RelationInvitationRequest();          
        try{
            if (Yii::$app->request->isPost) {
                $modelRelationInvitationRequest->load(Yii::$app->getRequest()->getBodyParams(), '');
                // cancel or delete relation/invite
                if($modelRelationInvitationRequest->id && $modelRelationInvitationRequest->id!=''){
                    $modelRelationInvitationRequest->scenario ='updateInvitation';
                    if(!$modelRelationInvitationRequest->validate()) {
                        $response['statusCode']=422;
                        $response['errors']=$modelRelationInvitationRequest->errors;
                        return $response;
                    } 
                    $invitationId = @(int) $modelRelationInvitationRequest->id;
                    $newStatus = $modelRelationInvitationRequest->status;
                    $resultInvitationRequest = $modelRelationInvitationRequest->findOne($invitationId);
           
                    if(!$resultInvitationRequest){
                        $response['statusCode']=422;
                        $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                        $response['errors']=$errors;
                        return $response;                   
                    }

                    if($resultInvitationRequest->created_by !=$loginUserId){
                        $response['statusCode']=422;
                        $errors['message'][] = Yii::$app->params['apiMessage']['common']['notAllowed'];
                        $response['errors']=$errors;
                        return $response;
                    
                    }
                    $resultInvitationRequest->status = $newStatus;
                    $resultInvitationRequest->save();
                    $message  = Yii::$app->params['apiMessage']['common']['actionSuccess'];
                    $response['message']=$message;
                    return $response;
            
                }else{
                    $modelRelationInvitationRequest->scenario ='invite';
                    if(!$modelRelationInvitationRequest->validate()) {
                        $response['statusCode']=422;
                        $response['errors']=$modelRelationInvitationRequest->errors;
                        return $response;
                    }            
                    $relationId =  @(int) $modelRelationInvitationRequest->relation_ship_id;
                    $userId = $modelRelationInvitationRequest->user_id;
                    $resultRelation = $modelRelation->findOne($relationId);
                    if(!$resultRelation){
                        $response['statusCode']=422;
                        $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                        $response['errors']=$errors;
                        return $response;
                    
                    }
                    // check pending request already sent
                    $checkExistingPendingRelation = $modelRelationInvitationRequest->find()->where([
                        'relation_ship_id'=>$relationId, 
                        'user_id'=>$userId,
                        'status'=>'1',
                        'created_by'=>$loginUserId
                        ])->one();
                    if($checkExistingPendingRelation){
                        $response['statusCode']=422;
                        $errors['message'][] = Yii::$app->params['apiMessage']['relation']['alreadyInvited'];
                        $response['errors']=$errors;
                        return $response;
                    }
                    //check relation laready exist for sinle relation
                    //like father , mother, son etc
                    if($resultRelation->use_once === 1){
                        $checkExistingSinleRelation = $modelRelationInvitationRequest->find()->where([
                            'relation_ship_id'=>$relationId, 
                            'status'=>'4',
                            'created_by'=>$loginUserId
                            ])->one();
                            
                        if($checkExistingSinleRelation){
                            $response['statusCode']=422;
                            $errors['message'][] = Yii::$app->params['apiMessage']['relation']['alreadyExist'];
                            $response['errors']=$errors;
                            return $response;
                        }
                    }
                    $modelRelationInvitationRequest->relation_ship_id = $relationId;
                    $modelRelationInvitationRequest->user_id = $userId;
                    if($modelRelationInvitationRequest->save()){
                        $modelNotification = new Notification();
                            $notificationInput = [];
                            $notificationData =  Yii::$app->params['pushNotificationMessage']['relationInvitation'];  
                            $notificationInput['referenceId'] = $modelRelationInvitationRequest->id;
                            $notificationInput['userIds'] =  [$userId];
                            $notificationInput['notificationData'] = $notificationData;
                            $modelNotification->createNotification($notificationInput);
                            $response['message']=Yii::$app->params['apiMessage']['relation']['inviteSuccess'];
                            return $response;
                    }
                }
                
            }
        }catch (ErrorException $e) {
            
            $response['statusCode'] = 500;
            $errors['message'] = 'error';
            $response['errors'] = $e->getMessage();

            return $response;
        }  
        
    }

    public function actionUpdateInvitation(){
        $loginUserId = Yii::$app->user->identity->id;
        $modelRelation = new RelationShip();
        $modelRelationInvitationRequest = new RelationInvitationRequest();  
        $modelRelationInvitationRequest->scenario ='updateInvitation';
        try{
            if (Yii::$app->request->isPut) {
                $modelRelationInvitationRequest->load(Yii::$app->getRequest()->getBodyParams(), '');
                if(!$modelRelationInvitationRequest->validate()) {
                    $response['statusCode'] = 422;
                    $response['errors'] = $modelRelationInvitationRequest->errors;
                    return $response;
                }
                $invitationId = @(int) $modelRelationInvitationRequest->id;
                $newStatus = $modelRelationInvitationRequest->status;
                $resultInvitationRequest = $modelRelationInvitationRequest->findOne($invitationId);
           
                if(!$resultInvitationRequest){
                    $response['statusCode']=422;
                    $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                    $response['errors']=$errors;
                    return $response;
                
                }

                if($resultInvitationRequest->status!=RelationInvitationRequest::STATUS_PENDING){
                    $response['statusCode']=422;
                    $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionAlready'];
                    $response['errors']=$errors;
                    return $response;
                
                }

                if($resultInvitationRequest->user_id !=$loginUserId){
                    $response['statusCode']=422;
                    $errors['message'][] = Yii::$app->params['apiMessage']['common']['notAllowed'];
                    $response['errors']=$errors;
                    return $response;
                
                }
                $resultInvitationRequest->status = $newStatus;  
                if($resultInvitationRequest->save()){
                    if($newStatus == RelationInvitationRequest::STATUS_ACCEPTED){
                        // create new relation for user with user send request
                        $userModel = new User();
                        $userData = $userModel->findOne($resultInvitationRequest->created_by);
                        $realationData = $modelRelation->findOne($resultInvitationRequest->relation_ship_id);
                        $modelNewInvitationRequest = new RelationInvitationRequest();
                        $relationId = ($userData->sex == 1) ? $realationData->male_relation_ship_id : $realationData->female_relation_ship_id;
                        $modelNewInvitationRequest->relation_ship_id = $relationId;
                        $modelNewInvitationRequest->user_id = $resultInvitationRequest->created_by;
                        $modelNewInvitationRequest->status = RelationInvitationRequest::STATUS_ACCEPTED;
                        $modelNewInvitationRequest->save();
                        $message  = Yii::$app->params['apiMessage']['common']['actionSuccess'];
                        $response['message']=$message;
                        return $response;
                    }
                }
         }
        }catch (ErrorException $e) {
            
            $response['statusCode'] = 500;
            $errors['message'] = 'error';
            $response['errors'] = $e->getMessage();

            return $response;
        }  
    }

    public function actionMyInvitation()
    {
        $userId= Yii::$app->user->identity->id;
        $modelRelationInvitationRequest = new RelationInvitationRequest();
        $query = $modelRelationInvitationRequest->find()
        ->where([
            'relation_invitation_request.user_id'=>$userId,
            'relation_invitation_request.status'  =>  RelationInvitationRequest::STATUS_PENDING
            ])
        ->with([
            'createdBy'=> function ($query) {
                $query->select([
                    'user.id',
                    'user.username',
                    'user.email',
                    'user.bio', 
                    'user.sex', 
                    'user.image'
                ]); 
            },
        ])
        ->with([
            'relationShip'=>function ($query) {
                $query->select(['relation_ship.id','relation_ship.name']); 
            }
        ]);
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

    public function actionMyRelation()
    {
        $userId= Yii::$app->user->identity->id;
        $modelRelationInvitationRequest = new RelationInvitationRequest();
        $query = $modelRelationInvitationRequest->find()
        ->where([
            'relation_invitation_request.created_by'=>$userId,
            'relation_invitation_request.status'  =>  [
                RelationInvitationRequest::STATUS_ACCEPTED,
                RelationInvitationRequest::STATUS_PENDING,
            ]
            ])
        ->with([
            'user'=> function ($query) {
                $query->select([
                    'user.id',
                    'user.username',
                    'user.email',
                    'user.bio',
                    'user.sex', 
                    'user.image',
                ]); 
            },
        ])
        ->with([
            'relationShip'=>function ($query) {
                $query->select(['relation_ship.id','relation_ship.name']); 
            }
        ]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['relations']=$dataProvider;
        return $response; 
    }

    public function actionUserRelation()
    {
        $userModel = new User();
        $userSettingModel = new UserSetting();
        $followerModel = new Follower();
        $LoggedInuserId= Yii::$app->user->identity->id;
        $userId = @(int) Yii::$app->getRequest()->getQueryParam('user_id');
        $userSetting = $userSettingModel->find()
                ->where(
                    [
                        'user_setting.user_id'=>$userId
                    ]
                )->one();
        
        $showRelation = true;
        if($userSetting && $userSetting->relation_setting==1){
            $showRelation = true;
        }else if($userSetting && $userSetting->relation_setting==2){
            $result = $followerModel->find()->where(['follower_id'=>$userId, 'user_id'=>$LoggedInuserId])->one();
            if($result){
                $showRelation = true;
            }else{
                $showRelation = false;
            }
        }else if($userSetting && $userSetting->relation_setting==0){
            $showRelation = false;
        }
        if($showRelation){
            $modelRelationInvitationRequest = new RelationInvitationRequest();
            $query = $modelRelationInvitationRequest->find()
            ->where([
                'relation_invitation_request.created_by'=>$userId,
                'relation_invitation_request.status'  =>  [
                    RelationInvitationRequest::STATUS_ACCEPTED,
                ]
                ])
            ->with([
                'user'=> function ($query) {
                    $query->select([
                        'user.id',
                        'user.username',
                        'user.email',
                        'user.bio',
                        'user.sex', 
                        'user.image',
                    ]); 
                },
            ])
            ->with([
                'relationShip'=>function ($query) {
                    $query->select(['relation_ship.id','relation_ship.name']); 
                }
            ]);
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => 20,
                ]
            ]);
        }else{
            $dataProvider = [];
        }
        
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['relations']=$dataProvider;
        return $response; 
    }

}
