<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
//use common\models\Category;
//use backend\models\CategorySearch;
use common\models\SupportRequest;
use common\models\User;
use common\models\Notification;

use yii\data\ActiveDataProvider;
use yii\imagine\Image;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;
use yii\filters\AccessControl;

/**
 * 
 */
class SupportRequestController extends Controller
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
                    'winning' => ['POST'],
                ],

            ],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::SUPPORT_REQUEST),
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all  models.
     * @return mixed
     */
    public function actionIndex()
    {
        
        $model = new SupportRequest();
        $query = $model->find()
        ->where(['<>','status',SupportRequest::STATUS_DELETED]);
       // ->orderBy(['id'=>SORT_DESC]);


        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['id' => SORT_DESC]]
        ]);


        return $this->render('index', [
            'model' => $model,
            'dataProvider' => $dataProvider
            
        ]);
    }

    /**
     * Displays a single Countryy model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model  = $this->findModel($id);
        
        return $this->render('view', [
            'model' =>   $model
        ]);
    }

    
    public function actionReply($id)
    {
        $modelSupportRequest = new SupportRequest();
        $modelUser = new User();
        
        
        $model = $this->findModel($id);
        $model->scenario= 'reply';

        if($model->is_reply){
            Yii::$app->session->setFlash('error', "Already replied");
            return $this->redirect(['view','id'=>$model->id]);
        }
        
         
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->is_reply =  $model::COMMON_YES;
                
            if($model->save(false)){

                $modelNotification = new Notification();
                $notificationInput = [];
                $notificationData =  Yii::$app->params['pushNotificationMessage']['supportRequestReply'];
                $replaceContent=[];   
                //$replaceContent['TITLE'] = $model->title;
                //$notificationData['body'] = $modelNotification->replaceContent($notificationData['body'],$replaceContent);   
            
                $userIds=[];
                $userIds[]   =   $model->user_id;
            
                $notificationInput['referenceId'] = $model->id;
                $notificationInput['userIds'] = $userIds;
                $notificationInput['notificationData'] = $notificationData;
                
                $modelNotification->createNotification($notificationInput);
                // end send notification                 
                Yii::$app->session->setFlash('success', "Support request replied successfully");
                return $this->redirect(['view','id'=>$model->id]);

            }
        }



        return $this->render('reply', [
            'model' => $model
    
        ]);
    
    }

    
    public function actionDelete($id)
    {
        $modelUser = new User();
        $modelUser->checkPageAccess();

        $model= $this->findModel($id);
        $model->status =  $model::STATUS_DELETED;
        if($model->save(false)){

            Yii::$app->session->setFlash('success', "Support Request deleted successfully");

            return $this->redirect(['index']);
        }
        
    }

    protected function findModel($id)
    {
        if (($model = SupportRequest::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
