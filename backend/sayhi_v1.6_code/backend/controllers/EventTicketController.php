<?php

namespace backend\controllers;

use Yii;
use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\EventTicket;
use backend\models\EventTicketSearch;
use common\models\Event;
use yii\helpers\ArrayHelper;
use yii\filters\AccessControl;

/**
 * 
 */
class EventTicketController extends Controller
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
        $searchModel = new EventTicketSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);


        $modelEvent = new Event();
        

        $resultEvent = $modelEvent->find()->select(['id','name'])->andWhere(['<>', 'status', Event::STATUS_DELETED])->all();
       
        $eventData = ArrayHelper::map($resultEvent,'id','name');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'eventData' =>$eventData
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

    /**
     * Creates a new Countryy model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
       
        $model = new EventTicket();
      
        $model->scenario = 'create';


        $modelEvent = new Event();
        $resultEvent = $modelEvent->find()->select(['id','name'])->andWhere(['<>', 'status', Event::STATUS_DELETED])->all();
        $eventData = ArrayHelper::map($resultEvent,'id','name');

        

        if ($model->load(Yii::$app->request->post()) ) {
            $modelUser = new User();
            $modelUser->checkPageAccess();
            
            if($model->validate()){
                $model->available_ticket = $model->limit;
                if($model->save()){
                    Yii::$app->session->setFlash('success', "Ticket saved successfully");
                    return $this->redirect(['index']);
                }
            }
            
        }

        return $this->render('create', [
            'model' => $model,
            'eventData'=>$eventData
            
        ]);
    }

    /**
     * Updates an existing Countryy model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        
        
        $model = $this->findModel($id);
        $model->scenario = 'update';
        $modelEvent = new Event();
        $resultEvent = $modelEvent->find()->select(['id','name'])->andWhere(['<>', 'status', Event::STATUS_DELETED])->all();
        $eventData = ArrayHelper::map($resultEvent,'id','name');

        if($model->load(Yii::$app->request->post())){
          
            if($model->save(false)){
                Yii::$app->session->setFlash('success', "Ticket updated updated successfully");
                return $this->redirect(['index']);
            };
                
        }
       
        return $this->render('update', [
            'model' => $model,
            'eventData'=>$eventData
       
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
        $userModel->status =  EventTicket::STATUS_DELETED;
        if($userModel->save(false)){

            Yii::$app->session->setFlash('success', "Ticket deleted successfully");

            return $this->redirect(['index']);
        }
        
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
        if (($model = EventTicket::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}