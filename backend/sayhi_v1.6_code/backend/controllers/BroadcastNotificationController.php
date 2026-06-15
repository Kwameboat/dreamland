<?php

namespace backend\controllers;

use Yii;
use app\models\User;
use backend\models\UserSearch;
use backend\models\ChangePassword;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use common\models\BroadcastNotification;
use backend\models\BroadcastNotificationSearch;
use common\models\BroadcastNotificationUser;
use common\models\DreamlandAudience;
use yii\filters\AccessControl;



/**
 * 
 */
class BroadcastNotificationController extends Controller
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
                        'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::BROADCAST_NOTIFICATIONS),
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function beforeAction($action) {     
        $this->enableCsrfValidation = false;     
        return parent::beforeAction($action);
     }

    /**
     * Lists all  models.
     * @return mixed
     */
    /*public function actionIndex()
    {
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->searchBroadcast(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }*/

    public function actionIndex()
    {
        $searchModel = new BroadcastNotificationSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }


    public function actionBroadcastUser($id)
    {
        $BroadcastNotificationUser = new BroadcastNotificationUser();
     //   $dataProvider = $searchModel->searchBroadcast(Yii::$app->request->queryParams);

        $query = BroadcastNotificationUser::find()
        ->where(['broadcast_notification_id'=>$id]);
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            
        ]);

      


        return $this->render('broadcast-user', [
            'searchModel' => [],
            'dataProvider' => $dataProvider,
        ]);
    }
    

    /**
     * Creates a new Countryy model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        Yii::$app->controller->enableCsrfValidation = false;
        $audience = (string) Yii::$app->request->get('audience', DreamlandAudience::VIEWERS);
        if (!DreamlandAudience::isValid($audience)) {
            $audience = DreamlandAudience::VIEWERS;
        }

        if (Yii::$app->request->post()) {
            $modelUser = new User();
            $modelUser->checkPageAccess();
            $postData = Yii::$app->request->post();
            $title = trim((string) ($postData['notification_title'] ?? ''));
            $messageBody = trim((string) ($postData['notification_message'] ?? ''));
            $audienceType = (string) ($postData['audience_type'] ?? DreamlandAudience::CUSTOM);
            if (!DreamlandAudience::isValid($audienceType)) {
                $audienceType = DreamlandAudience::CUSTOM;
            }

            $selectedRows = (string) ($postData['selectedRows'] ?? '');
            $customIds = array_filter(array_map('intval', explode(',', $selectedRows)));

            if ($audienceType === DreamlandAudience::CUSTOM && !$customIds) {
                Yii::$app->session->setFlash('error', 'Select at least one recipient or choose a target group.');
                return $this->redirect(['create', 'audience' => $audience]);
            }

            $userIdsAll = DreamlandAudience::resolveUserIds($audienceType, $customIds);
            if (!$userIdsAll) {
                Yii::$app->session->setFlash('error', 'No users found for the selected audience.');
                return $this->redirect(['create', 'audience' => $audience]);
            }

            foreach (array_chunk($userIdsAll, 100) as $userIds) {
                Yii::$app->dreamlandPush->notifyUsers($userIds, $title, $messageBody, [
                    'type' => \common\components\DreamlandPushService::TYPE_BROADCAST,
                    'saveInApp' => true,
                    'url' => '/',
                    'audienceGroup' => $audienceType,
                ]);
            }

            $modelBroadcastNotification = new BroadcastNotification();
            $modelBroadcastNotification->title = $title;
            $modelBroadcastNotification->message_body = $messageBody;
            $modelBroadcastNotification->audience_type = $audienceType;
            $modelBroadcastNotification->total_user = count($userIdsAll);
            if ($modelBroadcastNotification->save()) {
                $values = [];
                foreach ($userIdsAll as $userId) {
                    $values[] = [
                        'broadcast_notification_id' => $modelBroadcastNotification->id,
                        'user_id' => $userId,
                    ];
                }
                if ($values) {
                    Yii::$app->db->createCommand()->batchInsert(
                        'broadcast_notification_user',
                        ['broadcast_notification_id', 'user_id'],
                        $values
                    )->execute();
                }
                Yii::$app->session->setFlash(
                    'success',
                    'Broadcast sent to ' . count($userIdsAll) . ' ' . DreamlandAudience::label($audienceType) . '.'
                );
                return $this->redirect(['index']);
            }

            Yii::$app->session->setFlash('error', 'Could not save broadcast record.');
            return $this->redirect(['index']);
        }

        $query = match ($audience) {
            DreamlandAudience::CREATORS => DreamlandAudience::creatorQuery(),
            DreamlandAudience::ADMINS => DreamlandAudience::adminQuery(),
            default => DreamlandAudience::viewerQuery(),
        };

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
        ]);

        return $this->render('create-broadcast', [
            'searchModel' => [],
            'dataProvider' => $dataProvider,
            'audience' => $audience,
            'audienceLabels' => DreamlandAudience::labels(),
        ]);
    }

    /**
     * Deletes an existing Countryy model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $modelUser = new User();
        $modelUser->checkPageAccess();

        $userModel= $this->findModel($id);
        $userModel->status =  USER::STATUS_DELETED;
        $userModel->save(false);
        return $this->redirect(['index']);
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
        if (($model = User::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
