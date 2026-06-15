<?php

namespace backend\controllers;

use Yii;
//use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use common\models\Event;
use common\models\EventTicket;
use common\models\EventTicketBooking;
use backend\models\EventTicketBookingSearch;
use yii\filters\AccessControl;
/**
 * 
 */
class EventTicketBookingController extends Controller
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
                        'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::EVENT),
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
        $searchModel = new EventTicketBookingSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);


        $modelEvent = new Event();
        

        $resultEvent = $modelEvent->find()->select(['id','name'])->andWhere(['<>', 'status', Event::STATUS_DELETED])->all();

        
       
        $eventData = ArrayHelper::map($resultEvent,'id','name');
        $checkInData =   $searchModel->checkInData;


       // eventData

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'eventData' =>$eventData,
            'checkInData'=>$checkInData
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
    public function actionUpdate($id)
    {
        
        
        $model = $this->findModel($id);
       // $model->scenario = 'update';
        /*$modelEventTicketBooking = new EventTicketBooking();
        $resultEvent = $modelEvent->find()->select(['id','name'])->andWhere(['<>', 'status', Event::STATUS_DELETED])->all();
        $eventData = ArrayHelper::map($resultEvent,'id','name');
        */

        if($model->load(Yii::$app->request->post())){
          
            if($model->save(false)){
                Yii::$app->session->setFlash('success', "Ticket booking updated successfully");
                return $this->redirect(['index']);
            };
                
        }
       
        return $this->render('update', [
            'model' => $model
            //'eventData'=>$eventData
       
        ]);
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
        if (($model = EventTicketBooking::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}