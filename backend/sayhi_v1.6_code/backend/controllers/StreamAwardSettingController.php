<?php

namespace backend\controllers;

use Yii;
//use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\StreamAwardSetting;
use backend\models\StreamAwardSettingSearch;

/**
 * 
 */
class StreamAwardSettingController extends Controller
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
       
        $searchModel = new StreamAwardSettingSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    /**
     * Displays a single stream award model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */

        /**
     * Creates a new stream award model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
       
        $model = new StreamAwardSetting();
      
        $model->scenario = 'create';
       
        if ($model->load(Yii::$app->request->post()) ) {
           if($model->validate()){

                if($model->save()){
                    Yii::$app->session->setFlash('success', "Stream Award created successfully");
                    return $this->redirect(['index']);
                }
            }
            
        }

        return $this->render('create', [
            'model' => $model  
            
        ]);
    }


    /**
     * Deletes an existing stream award model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
         $model= $this->findModel($id);
        $model->status =  $model::STATUS_DELETED;
        if($model->save(false)){

            Yii::$app->session->setFlash('success', "Stream Award deleted successfully");

            return $this->redirect(['index']);
        }
    }


    /**
     * Finds the stream award model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Countryy the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = StreamAwardSetting::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

     
    /**
     * Updates an existing stream award model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        
        $model = $this->findModel($id);
      
        $model->scenario = 'update';
        if ($model->load(Yii::$app->request->post()) ) {
           
           if($model->validate()){
                
                if($model->save()){
                    Yii::$app->session->setFlash('success', "Stream Award updated data successfully");
                    return $this->redirect(['index']);
                }
            }
            
        }else{
            $model->created_at              = date('Y-m-d',$model->created_at);
        }  
        return $this->render('update', [
            'model' => $model,
        ]);
    
    }


}