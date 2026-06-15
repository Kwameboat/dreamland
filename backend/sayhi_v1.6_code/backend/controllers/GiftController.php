<?php

namespace backend\controllers;

use Yii;
use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\Gift;
use backend\models\GiftSearch;
use yii\web\UploadedFile;
use common\models\GiftCategory;
use yii\helpers\ArrayHelper;
use yii\filters\AccessControl;

/**
 * 
 */
class GiftController extends Controller
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
                        'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::GIFT),
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
        $searchModel = new GiftSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);


        $modelCategory = new GiftCategory();
        

        $resultCategory = $modelCategory->find()->select(['id','name'])->andWhere(['<>', 'status', GiftCategory::STATUS_DELETED])->all();
       
        $categoryData = ArrayHelper::map($resultCategory,'id','name');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'categoryData' =>$categoryData
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
       
        $model = new Gift();
      
        $model->scenario = 'create';
        $modelCategory = new GiftCategory();
        $resultCategory = $modelCategory->find()->select(['id','name'])->andWhere(['<>', 'status', GiftCategory::STATUS_DELETED])->all();
        $categoryData = ArrayHelper::map($resultCategory,'id','name');


        if ($model->load(Yii::$app->request->post()) ) {
            $modelUser = new User();
            $modelUser->checkPageAccess();
            
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if($model->validate()){
                if($model->imageFile){
                    
                    $type =  Yii::$app->fileUpload::TYPE_GIFT;
                    $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                    $model->image 		= 	  $files[0]['file']; 
                    
                }

                if($model->save()){
                    Yii::$app->session->setFlash('success', "Gift created successfully");
                    return $this->redirect(['index']);
                }
            }
            
        }

        return $this->render('create', [
            'model' => $model,
            'categoryData'=>$categoryData
            
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
        
        //echo Yii::$app->urlManagerFrontend->baseUrl;
        $modelCategory = new GiftCategory();
        $resultCategory = $modelCategory->find()->select(['id','name'])->andWhere(['<>', 'status', GiftCategory::STATUS_DELETED])->all();
        $categoryData = ArrayHelper::map($resultCategory,'id','name');


        $model = $this->findModel($id);

        $model->scenario = 'update';
       
        //if ($model->load(Yii::$app->request->post()) && $model->save()) {


        //if($model->load(Yii::$app->request->post()) && $model->validate()) {
        if($model->load(Yii::$app->request->post())){
            $modelUser = new User();
            $modelUser->checkPageAccess();
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if($model->imageFile){
                $type =  Yii::$app->fileUpload::TYPE_GIFT;
                $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                $model->image 		= 	  $files[0]['file']; 
                
            }
           
          
            if($model->save(false)){
                Yii::$app->session->setFlash('success', "Gift updated successfully");
                return $this->redirect(['index']);
            };
                
        }
       
        return $this->render('update', [
            'model' => $model,
            'categoryData'=>$categoryData
       
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
        $userModel->status =  Gift::STATUS_DELETED;
        if($userModel->save(false)){

            Yii::$app->session->setFlash('success', "Gift deleted successfully");

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
        if (($model = Gift::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}