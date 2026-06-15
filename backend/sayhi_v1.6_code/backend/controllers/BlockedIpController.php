<?php

namespace backend\controllers;

use Yii;
use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\BlockedIp;
use backend\models\BlockedIpSearch;
use common\models\UserLoginLog;
use yii\web\UploadedFile;

use yii\helpers\ArrayHelper;


/**
 * 
 */
class BlockedIpController extends Controller
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
        $searchModel = new BlockedIpSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);


        return $this->render('index', [
            'searchModel' => $searchModel,
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

    /**
     * Creates a new Countryy model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
       
        $model = new BlockedIp();
      
        $model->scenario = 'create';
        if ($model->load(Yii::$app->request->post()) ) {
            if($model->validate()){
                if($model->save()){
                    $ipAddress =  $model->ip_address;
                    $model->logoutUserWithLastLoginIp($ipAddress);
                    Yii::$app->session->setFlash('success', "IP Blocked successfully");
                    return $this->redirect(['index']);
                }
            }
        }
        return $this->render('create', [
            'model' => $model
            
            
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
        $preIpAddress = $model->ip_address;
        if($model->load(Yii::$app->request->post()) && $model->validate()) {
          
            if($model->save(false)){
                if($preIpAddress!=$model->ip_address){
                    $ipAddress =  $model->ip_address;
                    $model->logoutUserWithLastLoginIp($ipAddress);
                }
                Yii::$app->session->setFlash('success', "Blocked IP updated successfully");
                return $this->redirect(['index']);
            };
                
        }
       
        return $this->render('update', [
            'model' => $model
            
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
        if($userModel->delete()){
            Yii::$app->session->setFlash('success', "Blocked IP deleted successfully");
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
        if (($model = BlockedIp::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}