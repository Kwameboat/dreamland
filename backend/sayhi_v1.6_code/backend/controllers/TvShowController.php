<?php

namespace backend\controllers;

use Yii;
use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\TvShow;
use backend\models\TvShowSearch;
// use backend\models\LiveTvSearch;
use yii\web\UploadedFile;
use common\models\Category;
use common\models\LiveTv;
use common\models\LiveTvCategory;
use yii\helpers\ArrayHelper;
use common\models\Language;

/**
 * 
 */
class TvShowController extends Controller
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
        // echo "hello";
        $searchModel = new TvShowSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $modelCategory = new Category();
        $resultCategory = $modelCategory->find()->select(['id','name'])->where(['and',['type'=>[3]]])->andWhere(['and', 'status', Category::STATUS_ACTIVE])->all();
        $categoryData = ArrayHelper::map($resultCategory,'id','name');

        $modelChannel = new LiveTv();
        $resultChannel = $modelChannel->find()->select(['id','name'])->all();
        $channelData = ArrayHelper::map($resultChannel,'id','name');

        $languageModel = new Language();
        $resultLanguage = $languageModel->find()->select(['id','name'])->all();
        $languageData = ArrayHelper::map($resultLanguage,'name','name');
        // print_r( $languageData);
        // exit("fgbj");
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'categoryData' =>$categoryData,
            'channelData'=>$channelData,
            'languageData'=>$languageData
        ]);
    }

    /**
     * Displays a single Countryy model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    // public function actionView($id)
    // {
    //     // $searchModel = new TvShowSearch();
    //     // $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

    //     // $modelCategory = new Category();
    //     // $resultCategory = $modelCategory->find()->select(['id','name'])->where(['and',['type'=>[3]]])->andWhere(['and', 'status', Category::STATUS_ACTIVE])->all();
    //     // $categoryData = ArrayHelper::map($resultCategory,'id','name');

    //     // $modelChannel = new LiveTv();
    //     // $resultChannel = $modelChannel->find()->select(['id','name'])->all();
    //     // $channelData = ArrayHelper::map($resultChannel,'id','name');

    //     // return $this->render('view', [
    //     //     'searchModel' => $searchModel,
    //     //     'dataProvider' => $dataProvider,
    //     //     'categoryData' =>$categoryData,
    //     //     'channelData'=>$channelData
    //     // ]);
    //     $this->redirect(\Yii::$app->urlManager->createUrl(["tv-show-episode/view", 'id' => $id]));
    // }

    public function actionView($id)
    {
        $model  = $this->findModel($id);
           $searchModel = new TvShowSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

         $modelCategory = new Category();
        $resultCategory = $modelCategory->find()->select(['id','name'])->where(['and',['type'=>[3]]])->andWhere(['and', 'status', Category::STATUS_ACTIVE])->all();
         $categoryData = ArrayHelper::map($resultCategory,'id','name');

         $modelChannel = new LiveTv();
         $resultChannel = $modelChannel->find()->select(['id','name'])->all();
         $channelData = ArrayHelper::map($resultChannel,'id','name');
        
        return $this->render('view', [
            'model' =>   $model,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'categoryData' =>$categoryData,
             'channelData'=>$channelData
        ]);
    }
        /**
     * Creates a new Countryy model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
       
        $model = new TvShow();
      
        $model->scenario = 'create';
        $modelCategory = new Category();
        $resultCategory = $modelCategory->find()->select(['id','name'])->where(['and',['type'=>[3]]])->andWhere(['and', 'status', Category::STATUS_ACTIVE])->all();
        $categoryData = ArrayHelper::map($resultCategory,'id','name');

        $modelChannel = new LiveTv();
        $resultChannel = $modelChannel->find()->select(['id','name'])->all();
        $channelData = ArrayHelper::map($resultChannel,'id','name');
       
        
        $languageModel = new Language();
        $resultLanguage = $languageModel->find()->select(['id','name'])->all();
        $languageData = ArrayHelper::map($resultLanguage,'name','name');

        if ($model->load(Yii::$app->request->post()) ) {
            $model->show_time              = strtotime($model->show_time);
            $model->created_at = strtotime("now");
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if($model->validate()){
                if($model->imageFile){
                    
                    $type = Yii::$app->fileUpload::TYPE_TV_SHOW;
                    $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                    $model->image 		= 	  $files[0]['file']; 
                    
                }

                if($model->save()){
                    Yii::$app->session->setFlash('success', "Tv Show created successfully");
                    return $this->redirect(['index']);
                }
            }
            
        }

        return $this->render('create', [
            'model' => $model,
            'categoryData'=>$categoryData,
            'channelData'=>$channelData,
            'languageData'=>$languageData
            
            
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
        $model= $this->findModel($id);
        $model->status =  $model::STATUS_DELETED;
        if($model->save(false)){

            Yii::$app->session->setFlash('success', "Tv Show deleted successfully");

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
        if (($model = TvShow::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
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
        // $model = new TvShow();
      
        $model->scenario = 'update';
        $modelCategory = new Category();
        $resultCategory = $modelCategory->find()->select(['id','name'])->where(['and',['type'=>[3]]])->andWhere(['and', 'status', Category::STATUS_ACTIVE])->all();
        $categoryData = ArrayHelper::map($resultCategory,'id','name');

        $modelChannel = new LiveTv();
        $resultChannel = $modelChannel->find()->select(['id','name'])->all();
        $channelData = ArrayHelper::map($resultChannel,'id','name');
       

        $languageModel = new Language();
        $resultLanguage = $languageModel->find()->select(['id','name'])->all();
        $languageData = ArrayHelper::map($resultLanguage,'name','name');

        if ($model->load(Yii::$app->request->post()) ) {
            $modelUser = new User();
            $modelUser->checkPageAccess();
            $model->show_time              = strtotime($model->show_time);
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if($model->validate()){
                if($model->imageFile){
                    
                    $type =  Yii::$app->fileUpload::TYPE_TV_SHOW;
                    $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                    $model->image 		= 	  $files[0]['file']; 
                    
                }
                if($model->save()){
                    Yii::$app->session->setFlash('success', "Tv Show updated data successfully");
                    return $this->redirect(['index']);
                }
            }
            
        }else{
            $model->created_at              = date('Y-m-d',$model->created_at);
            $model->show_time              = date('Y-m-d h:i',$model->show_time);
        }  
        return $this->render('update', [
            'model' => $model,
            'categoryData'=>$categoryData,
            'channelData'=>$channelData,
            'languageData'=>$languageData
    
        ]);
    
    }

    /**
     * Displays a single list model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionList($id)
    {
        $this->redirect(\Yii::$app->urlManager->createUrl(["tv-show-episode/view", 'id' => $id]));
    }



}