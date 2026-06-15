<?php

namespace backend\controllers;

use Yii;
use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\Podcast;
use backend\models\PodcastSearch;
use yii\web\UploadedFile;
use common\models\PodcastCategory;
use yii\helpers\ArrayHelper;
use common\models\Category;
use common\models\Business;
use backend\models\BusinessSearch;
use common\models\City;
use common\models\BusinessExampleImage;
/**
 * 
 */
class BusinessController extends Controller
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
        $searchModel = new BusinessSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);


        $modelCategory = new Category();
        

        $resultCategory = $modelCategory->find()->select(['id','name'])->where(['type'=>Category::TYPE_BUSINESS_CATEGORY])->andWhere(['<>', 'status', Category::STATUS_DELETED])->all();
       
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
       
        $model = new Business();
      
        $model->scenario = 'create';
        $modelCategory = new Category();
        $resultCategory = $modelCategory->find()->select(['id','name'])->where(['type'=>Category::TYPE_BUSINESS_CATEGORY])->andWhere(['<>', 'status', Category::STATUS_DELETED])->all();
        $categoryData = ArrayHelper::map($resultCategory,'id','name');

        // $modelCity = new City();
        // $resultCity = $modelCity->find()->select(['id','name'])->all();
        // $cityData = ArrayHelper::map($resultCity,'id','name');

        $modelBusinessExampleImage = new BusinessExampleImage();
        if ($model->load(Yii::$app->request->post()) ) {
            
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            $model->exampleFile = UploadedFile::getInstances($model, 'exampleFile');
            if($model->validate()){
                if($model->imageFile){
                    
                    $type =    Yii::$app->fileUpload::TYPE_BUSINESS;
                    $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                    $model->image 		= 	  $files[0]['file']; 
                    
                }

                if($model->save()){
                    $images =[];
                    foreach ($model->exampleFile as $file) {
                    
                        $type =     Yii::$app->fileUpload::TYPE_BUSINESS;
                        $files  = Yii::$app->fileUpload->uploadFile($file,$type,false);
                        $images[]	= 	  $files[0]['file']; 

                    }
                    if(count($images)>0){
                        $modelBusinessExampleImage->addPhoto($model->id,$images);
                    }
                   
                    Yii::$app->session->setFlash('success', "Business created successfully");
                    return $this->redirect(['index']);
                }
            }
            
        }

        return $this->render('create', [
            'model' => $model,
            'categoryData'=>$categoryData,
            // 'city' => $cityData,
            
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
        $modelCategory = new Category();
        $resultCategory = $modelCategory->find()->select(['id','name'])->where(['type'=>Category::TYPE_BUSINESS_CATEGORY])->andWhere(['<>', 'status', Category::STATUS_DELETED])->all();
        $categoryData = ArrayHelper::map($resultCategory,'id','name');


        $model = $this->findModel($id);

        $model->scenario = 'update';
       
        //if ($model->load(Yii::$app->request->post()) && $model->save()) {

            $modelBusinessExampleImage = new BusinessExampleImage();
        //if($model->load(Yii::$app->request->post()) && $model->validate()) {
        if($model->load(Yii::$app->request->post())){
            $modelUser = new User();
            $modelUser->checkPageAccess();
    

            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            $model->exampleFile = UploadedFile::getInstances($model, 'exampleFile');
            
            if($model->imageFile){
                
                $type =     Yii::$app->fileUpload::TYPE_BUSINESS;
                $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                $model->image 		= 	  $files[0]['file']; 
                
            }
           
          
            if($model->save(false)){
                $s3 = Yii::$app->get('s3');
                    if($model->deletePhoto){

                        $deletePhotoIds=[];
                        foreach($model->deletePhoto as $photoId){
                            if((int)$photoId>0){
                                $resultPhoto = $modelBusinessExampleImage->findOne($photoId);
                                $deletePhotoIds[]=$photoId;
                            }
                        }    
                        
                        if(count($deletePhotoIds)){
                            $modelBusinessExampleImage->deleteAll(['IN','id',$deletePhotoIds]);
                        }
                        
                    }


                    $images =[];
                    foreach ($model->exampleFile as $file) {

                        $type       =     Yii::$app->fileUpload::TYPE_BUSINESS;
                        $files      =     Yii::$app->fileUpload->uploadFile($file,$type,false);
                        $images[]   = 	  $files[0]['file']; 

                    }
                    if(count($images)>0){
                        $modelBusinessExampleImage->addPhoto($model->id,$images);
                    }
                Yii::$app->session->setFlash('success', "Business updated successfully");
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
        $userModel->status =  Business::STATUS_DELETED;
        if($userModel->save(false)){

            Yii::$app->session->setFlash('success', "Business deleted successfully");

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
        if (($model = Business::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}