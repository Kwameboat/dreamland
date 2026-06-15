<?php
namespace api\modules\v1\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\data\ActiveDataProvider;
use api\modules\v1\models\PickleballCourt;
use api\modules\v1\models\PickleballMatch;
use api\modules\v1\models\PickleballMatchSearch;
use api\modules\v1\models\PickleballMatchTeam;
use api\modules\v1\models\PickleballTeamPlayer;
use api\modules\v1\models\Notification;
use api\modules\v1\models\User;



class PickleballController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\pickleballMatch';
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
            //'except'=>['ad-search'],
            'authMethods' => [
                HttpBearerAuth::className()
            ],
        ];
        return $behaviors;
    }


    public function actionIndex()
    {
        $model = new PickleballCourtSearch();
        $result = $model->search(Yii::$app->request->queryParams);
        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        $response['court'] = $result;
        return $response;


    }

    public function actionCreateMatch()
    {
        $userId = Yii::$app->user->identity->id;
        $username = Yii::$app->user->identity->username;


        $model = new PickleballMatch();
        $model->scenario = 'create';
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if (!$model->validate()) {
                $response['statusCode'] = 422;
                $response['errors'] = $model->errors;
                return $response;
            }
            $modelPickleballCourt = new PickleballCourt();
            $modelPickleballCourtResult = $modelPickleballCourt->findOne($model->court_id);
            if (!$modelPickleballCourtResult) {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['pickleball']['courtNotExist'];
                $response['errors'] = $errors;
                return $response;
            }

            $courtName = @$modelPickleballCourtResult->name;
            $matchTime = Yii::$app->formatter->asDatetime($model->start_time);

            if ($model->save(false)) {
                $matchId = $model->id;
                foreach ($model->match_team as $team) {
                    $teamName = $team['name'];
                    $modelPickleballMatchTeam = new PickleballMatchTeam();
                    $modelPickleballMatchTeam->match_id = $matchId;
                    $modelPickleballMatchTeam->name = $teamName;
                    if ($modelPickleballMatchTeam->save(false)) {
                        $teamId = $modelPickleballMatchTeam->id;

                        foreach ($team['player'] as $player) {
                            $userIds = [];
                            $playerId = $player['player_id'];
                            $isSendInvitation = $player['is_send_invitation'];
                            $modelPickleballTeamPlayer = new PickleballTeamPlayer();

                            $modelPickleballTeamPlayer->match_id = $matchId;
                            $modelPickleballTeamPlayer->team_id = $teamId;
                            $modelPickleballTeamPlayer->player_id = $playerId;
                            if ($isSendInvitation) {
                                $status = PickleballTeamPlayer::STATUS_PENDING;
                                /// send notificaton to invitation
                                $userIds[] = $playerId;

                            } else {
                                $status = PickleballTeamPlayer::STATUS_ACTIVE;
                            }
                            $modelPickleballTeamPlayer->status = $status;
                            if ($modelPickleballTeamPlayer->save(false)) {

                                $isProcess = true;
                                ////send push notification invitation
                                if ($userIds) {
                                    $modelNotification = new Notification();
                                    $notificationInput = [];
                                    $notificationData = Yii::$app->params['pushNotificationMessage']['gameInvitation'];
                                    $replaceContent = [];
                                    $replaceContent['INVITED_BY'] = $username;
                                    $replaceContent['COURT_NAME'] = $courtName;
                                    $replaceContent['DATE_TIME'] = $matchTime;
                                    $notificationData['body'] = $modelNotification->replaceContent($notificationData['body'], $replaceContent);
                                    $notificationInput['referenceId'] = $modelPickleballTeamPlayer->id;
                                    $notificationInput['userIds'] = $userIds;
                                    $notificationInput['notificationData'] = $notificationData;
                                    $modelNotification->createNotification($notificationInput);
                                    // end send notification 
                                }
                            }
                        }

                    }
                }
                $response['message'] = Yii::$app->params['apiMessage']['pickleball']['matchCreated'];
                $response['match_id'] = $model->id;
                return $response;
            } else {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors'] = $errors;
                return $response;
            }
        }
    }

    public function actionAddTeamPlayer()
    {
        $userId = Yii::$app->user->identity->id;
        $username = Yii::$app->user->identity->username;
        $model = new PickleballTeamPlayer();
        $model->scenario = 'addPlayer';
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if (!$model->validate()) {
                $response['statusCode'] = 422;
                $response['errors'] = $model->errors;
                return $response;
            }
            $playerId = $model->player_id;
            $teamId = $model->team_id;
            $matchId = $model->match_id;

            $statusArr = [];
            $statusArr[] = PickleballTeamPlayer::STATUS_PENDING;
            $statusArr[] = PickleballTeamPlayer::STATUS_ACTIVE;

            $isAlreadyCount = $model->find()->where(['player_id' => $playerId, 'team_id' => $teamId, 'status' => $statusArr])->count();
            if ($isAlreadyCount) {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['pickleball']['playerAlredyExist'];
                $response['errors'] = $errors;
                return $response;
            }

            $modelPickleballMatchTeam = new PickleballMatchTeam();
            $resultPickleballMatchTeam = $modelPickleballMatchTeam->findOne($teamId);

            $startTime = $resultPickleballMatchTeam->pickleballMatch->start_time;
            $matchTime = Yii::$app->formatter->asDatetime($startTime);
            $courtName = $resultPickleballMatchTeam->pickleballMatch->court->name;
            $matchId = $resultPickleballMatchTeam->match_id;

            $modelPickleballTeamPlayer = new PickleballTeamPlayer();

            $modelPickleballTeamPlayer->match_id = $matchId;
            $modelPickleballTeamPlayer->team_id = $teamId;
            $modelPickleballTeamPlayer->player_id = $playerId;
            $modelPickleballTeamPlayer->status = PickleballTeamPlayer::STATUS_PENDING;
            if ($modelPickleballTeamPlayer->save(false)) {
                ////send push notification invitation
                $userIds = [];
                $userIds[] = $playerId;
                if ($userIds) {
                    $modelNotification = new Notification();
                    $notificationInput = [];
                    $notificationData = Yii::$app->params['pushNotificationMessage']['gameInvitation'];
                    $replaceContent = [];
                    $replaceContent['INVITED_BY'] = $username;
                    $replaceContent['COURT_NAME'] = $courtName;
                    $replaceContent['DATE_TIME'] = $matchTime;
                    $notificationData['body'] = $modelNotification->replaceContent($notificationData['body'], $replaceContent);
                    $notificationInput['referenceId'] = $modelPickleballTeamPlayer->id;
                    $notificationInput['userIds'] = $userIds;
                    $notificationInput['notificationData'] = $notificationData;
                    $modelNotification->createNotification($notificationInput);
                    // end send notification 
                }
                $response['message'] = Yii::$app->params['apiMessage']['pickleball']['playerAdded'];
                return $response;
            } else {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors'] = $errors;
                return $response;
            }
        }
    }

    public function actionRemoveTeamPlayer()
    {
        $userId = Yii::$app->user->identity->id;
        $username = Yii::$app->user->identity->username;
        $model = new PickleballTeamPlayer();
        $model->scenario = 'removePlayer';
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if (!$model->validate()) {
                $response['statusCode'] = 422;
                $response['errors'] = $model->errors;
                return $response;
            }
            $id = $model->id;

            $resultPickleballTeamPlayer = $model->findOne($id);
            if (!$resultPickleballTeamPlayer) {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors'] = $errors;
                return $response;
            }
            if ($resultPickleballTeamPlayer->status == PickleballTeamPlayer::STATUS_DELETED) {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors'] = $errors;
                return $response;
            }

            if ($resultPickleballTeamPlayer->pickleballMatch->created_by != $userId) {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['notAllowed'];
                $response['errors'] = $errors;
                return $response;
            }
            $resultPickleballTeamPlayer->status = PickleballTeamPlayer::STATUS_DELETED;
            if ($resultPickleballTeamPlayer->save(false)) {
                $response['message'] = Yii::$app->params['apiMessage']['pickleball']['playerRemoved'];
                return $response;
            } else {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors'] = $errors;
                return $response;
            }
        }
    }

    public function actionReplyMatchInvitation()
    {
        $userId = Yii::$app->user->identity->id;
        $username = Yii::$app->user->identity->username;

        $model = new PickleballTeamPlayer();

        $model->scenario = 'replyInvitation';
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if (!$model->validate()) {
                $response['statusCode'] = 422;
                $response['errors'] = $model->errors;
                return $response;
            }

            $resultPickleballTeamPlayer = $model->find()->where(['id' => $model->id, 'player_id' => $userId])->one();
            if (!$resultPickleballTeamPlayer) {

                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors'] = $errors;
                return $response;

            }

            if ($resultPickleballTeamPlayer->status != PickleballTeamPlayer::STATUS_PENDING) {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionAlready'];
                $response['errors'] = $errors;
                return $response;
            }
            $newStatus = $model->status;

            $templateName = '';
            $msgResponse = '';
            if ($newStatus == PickleballTeamPlayer::STATUS_REJECTED) {
                $templateName = 'gameInvitationRejected';
                $resultPickleballTeamPlayer->status = $newStatus;
                $msgResponse = Yii::$app->params['apiMessage']['pickleball']['invitationRejected'];

            } else if ($newStatus == PickleballTeamPlayer::STATUS_ACCEPTED) {
                $templateName = 'gameInvitationAcepted';
                $resultPickleballTeamPlayer->status = PickleballTeamPlayer::STATUS_ACTIVE;
                $msgResponse = Yii::$app->params['apiMessage']['pickleball']['invitationAccepted'];
            }


            if ($resultPickleballTeamPlayer->save(false)) {
                $modelMatch = new PickleballMatch();
                $resultMatch = $modelMatch->findOne($resultPickleballTeamPlayer->match_id);


                $modelPickleballCourt = new PickleballCourt();
                $modelPickleballCourtResult = $modelPickleballCourt->findOne($resultMatch->court_id);

                $courtName = @$modelPickleballCourtResult->name;
                $matchTime = Yii::$app->formatter->asDatetime($resultMatch->start_time);


                $userIds = [];
                $userIds[] = $resultMatch->created_by;
                if ($userIds) {
                    $modelNotification = new Notification();
                    $notificationInput = [];
                    $notificationData = Yii::$app->params['pushNotificationMessage'][$templateName];
                    $replaceContent = [];
                    $replaceContent['USER'] = $username;
                    $replaceContent['COURT_NAME'] = $courtName;
                    $replaceContent['DATE_TIME'] = $matchTime;
                    $notificationData['body'] = $modelNotification->replaceContent($notificationData['body'], $replaceContent);
                    $notificationInput['referenceId'] = $resultPickleballTeamPlayer->id;
                    $notificationInput['userIds'] = $userIds;
                    $notificationInput['notificationData'] = $notificationData;
                    $modelNotification->createNotification($notificationInput);
                    // end send notification 
                }


                $response['message'] = $msgResponse;

                return $response;
            } else {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors'] = $errors;
                return $response;
            }
        }
    }
    public function actionDeclareMatchResult()
    {
        $userId = Yii::$app->user->identity->id;

        $model = new PickleballMatch();
        $model->scenario = 'declareResult';
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if (!$model->validate()) {
                $response['statusCode'] = 422;
                $response['errors'] = $model->errors;
                return $response;
            }
            $matchId = $model->id;

            $resultMatch = $model->findOne($matchId);
            if (!$resultMatch) {

                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors'] = $errors;
                return $response;

            }
            if ($resultMatch->status != PickleballMatch::STATUS_ACTIVE) {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionAlready'];
                $response['errors'] = $errors;
                return $response;
            }

            $resultMatch->status = PickleballMatch::STATUS_COMPLETED;
            $resultMatch->winner_team_id = $model->winner_team_id;
            $resultMatch->result_declared_at = time();
            if($resultMatch->save(false)){
                foreach ($model->match_team as $team) {
                    
                    $teamId = $team['team_id'];
                    $modelPickleballMatchTeam = new PickleballMatchTeam();
                    $resultPickleballMatchTeam = $modelPickleballMatchTeam->findOne($team['team_id'] );
                    $resultPickleballMatchTeam->team_point =  $team['team_point'];
                    $winner_status = 0;
                    if($model->winner_team_id == $team['team_id']){
                        $winner_status =PickleballMatchTeam::WINNER_STATUS_NOT_WIN;
                    }else{
                        $winner_status =PickleballMatchTeam::WINNER_STATUS_NOT_LOSS;
                    }
                    $resultPickleballMatchTeam->winner_status =  $winner_status;
                    if($resultPickleballMatchTeam->save(false)){
                        foreach ($team['player'] as $player) {
                            $modelPickleballTeamPlayer = new PickleballTeamPlayer();
                            $playerId = $player['player_id'];
                            $resultPickleballTeamPlayer  = $modelPickleballTeamPlayer->find()->where(['player_id'=>$playerId,'team_id'=>$teamId,'status'=> PickleballTeamPlayer::STATUS_ACTIVE])->one();
                            if($resultPickleballTeamPlayer){
                                $resultPickleballTeamPlayer->point_gain = $player['point_gain'];
                                $resultPickleballTeamPlayer->save(false);
                            }
                        }    
                    }
                }

                $response['message'] = Yii::$app->params['apiMessage']['pickleball']['matchResultDeclared'];
                return $response;
            } else {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors'] = $errors;
                return $response;
            }
        }
    }
    public function actionView($id)
    {
        $userId = Yii::$app->user->identity->id;
        $model = new PickleballMatch();
        $result = $model->find()->where(['pickleball_match.id' => $id])
            ->joinWith([
                'matchTeam.teamPlayer.playerDetail' => function ($query) {
                    $query->select(['name', 'username', 'email', 'image', 'id', 'is_chat_user_online', 'chat_last_time_online', 'location', 'latitude', 'longitude']);
                }
            ])
            ->one();
        $response['message'] = Yii::$app->params['apiMessage']['common']['recordFound'];
        $response['match'] = $result;
        return $response;

    }

    public function actionMatchList()
    {

        $model = new PickleballMatchSearch();
        $result = $model->searchMatch(Yii::$app->request->queryParams);
        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        $response['match'] = $result;
        return $response;

    }

    
    public function actionTopPlayer()
    {

        $userId    =     Yii::$app->user->identity->id;
        $modelUser  =   new User();

        $query = $modelUser->find()
        ->select(['user.id','user.name','user.username','user.email','user.unique_id','user.bio','user.description','user.image','user.is_verified','user.country_code','user.phone','user.country','user.city','user.sex','user.dob','user.is_chat_user_online','user.chat_last_time_online','user.profile_category_type'])
        ->where(['user.role'=>User::ROLE_CUSTOMER])
        ->andwhere(['user.status'=>User::STATUS_ACTIVE])
        ->joinWith('pickleballTeamPlayer')
        ->addSelect(['sum(pickleball_team_player.point_gain) as totalPoint'])
        ->orderBy(['totalPoint'=>SORT_DESC])
        ->groupBy(['user.id']);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['player']=$dataProvider;
        return $response; 
    }




    protected function findModel($id)
    {
        if (($model = PickleballMatch::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }


}


