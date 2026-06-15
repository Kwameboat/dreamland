<?php

namespace backend\controllers;

use Yii;
//use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\EventOrganisor;
use backend\models\EventOrganisorSearch;
use common\models\Event;
use yii\helpers\ArrayHelper;

use yii\web\UploadedFile;

/**
 * 
 */
class EventOrganisorController extends Controller
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
        ];
    }

    /**
     * Lists all  models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new EventOrganisorSearch();
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
       
        $model = new EventOrganisor();
            
        $model->scenario = 'create';


        $modelEvent = new Event();
        $resultEvent = $modelEvent->find()->select(['id','name'])->andWhere(['<>', 'status', Event::STATUS_DELETED])->all();
        $eventData = ArrayHelper::map($resultEvent,'id','name');

        

        if ($model->load(Yii::$app->request->post()) ) {
            
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');

            if($model->validate()){
                if($model->imageFile){
                    
                    $type =  Yii::$app->fileUpload::TYPE_EVENT_ORGANISOR;
                    $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                    $model->image 		= 	  $files[0]['file'];
                    
                }

                if($model->save()){
                    Yii::$app->session->setFlash('success', "Sponsore added successfully");
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
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');

            if($model->imageFile){
                    
                $type =   Yii::$app->fileUpload::TYPE_EVENT_ORGANISOR;
                $files =  Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                $model->image 		= 	  $files[0]['file'];
                
            }
          
            if($model->save(false)){
                Yii::$app->session->setFlash('success', "Sponsore updated successfully");
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
        $userModel= $this->findModel($id);
        $userModel->status =  EventOrganisor::STATUS_DELETED;
        if($userModel->save(false)){

            Yii::$app->session->setFlash('success', "Sponsore deleted successfully");

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
        if (($model = EventOrganisor::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}