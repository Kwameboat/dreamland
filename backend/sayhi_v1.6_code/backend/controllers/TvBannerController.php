<?php

namespace backend\controllers;

use Yii;
//use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\TvShow;
use backend\models\TvShowSearch;
// use backend\models\LiveTvSearch;
use yii\web\UploadedFile;
use common\models\Category;
use common\models\TvBanner;
use common\models\LiveTvCategory;
use yii\helpers\ArrayHelper;
use common\models\Language;
use common\models\LiveTv;
use common\models\TvShowEpisode;
use backend\models\TvBannerSearch;

/**
 * 
 */
class TvBannerController extends Controller
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
        $searchModel = new TvBannerSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $modelCategory = new Category();
        $resultCategory = $modelCategory->find()->select(['id','name'])->where(['and',['type'=>[3]]])->andWhere(['and', 'status', Category::STATUS_ACTIVE])->all();
        $categoryData = ArrayHelper::map($resultCategory,'id','name');

        $modelChannel = new TvBanner();
        $resultChannel = $modelChannel->find()->select(['id','name'])->all();
        $channelData = ArrayHelper::map($resultChannel,'id','name');
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'channelData'=>$channelData

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
        $this->redirect(\Yii::$app->urlManager->createUrl(["tv-show-episode/view", 'id' => $id]));
    }

        /**
     * Creates a new Countryy model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
       
        $model = new TvBanner();
      
        $model->scenario = 'create';
        $modelCategory = new Category();
        $resultCategory = $modelCategory->find()->select(['id','name'])->where(['and',['type'=>[3]]])->andWhere(['and', 'status', Category::STATUS_ACTIVE])->all();
        $categoryData = ArrayHelper::map($resultCategory,'id','name');
        $searchData = array();
       // show default banner type
        $modelCategory = new TvShow();
        $resultCategory = $modelCategory->find()->select(['id','name'])->where(['and',['status'=>TvShow::STATUS_ACTIVE]])->all();
        $searchData = ArrayHelper::map($resultCategory,'id','name');

        $modelChannel = new TvBanner();
        $resultChannel = $modelChannel->find()->select(['id','name'])->all();
        $channelData = ArrayHelper::map($resultChannel,'id','name');
       

        if ($model->load(Yii::$app->request->post()) ) {
            
            $model->created_at = strtotime("now");
            $model->updated_at = strtotime("now");
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if($model->validate()){               
            $model->start_time              = strtotime($model->start_time);
            $model->end_time              = strtotime($model->end_time);
                if($model->imageFile){
                    
                    $type =  Yii::$app->fileUpload::TYPE_TV_BANNER;
                    $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                    $model->cover_image 		= 	  $files[0]['file']; 
                    
                }

                if($model->save()){
                    Yii::$app->session->setFlash('success', "Banner Show created successfully");
                    return $this->redirect(['index']);
                }
            }
            
        }

        return $this->render('create', [
            'model' => $model,
            'categoryData'=>$categoryData,
            'channelData'=>$channelData,
            'searchData' => $searchData,
            
            
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
         $model= $this->findModel($id);
        $model->status =  $model::STATUS_DELETED;
        if($model->save(false)){

            Yii::$app->session->setFlash('success', "Tv Banner deleted successfully");

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
        if (($model = TvBanner::findOne($id)) !== null) {
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

        $modelChannel = new TvBanner();
        $resultChannel = $modelChannel->find()->select(['id','name'])->all();
        $channelData = ArrayHelper::map($resultChannel,'id','name');
       

        $languageModel = new Language();
        $resultLanguage = $languageModel->find()->select(['id','name'])->all();
        $languageData = ArrayHelper::map($resultLanguage,'name','name');
        $searchData = array();
        // show default banner type
         $modelCategory = new TvShow();
         $resultCategory = $modelCategory->find()->select(['id','name'])->where(['and',['status'=>TvShow::STATUS_ACTIVE]])->all();
         $searchData = ArrayHelper::map($resultCategory,'id','name');

        if ($model->load(Yii::$app->request->post()) ) {
            $model->start_time              = strtotime($model->start_time);
            $model->end_time              = strtotime($model->end_time);
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if($model->validate()){
                if($model->imageFile){
                    
                    $type = Yii::$app->fileUpload::TYPE_TV_BANNER;
                    $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                    $model->cover_image 		= 	  $files[0]['file']; 
                    
                }
                if($model->save()){
                    Yii::$app->session->setFlash('success', "Tv Banner updated data successfully");
                    return $this->redirect(['index']);
                }
            }
            
        }else{
            $model->start_time              = date('Y-m-d h:i',$model->start_time);
            $model->end_time              = date('Y-m-d h:i',$model->end_time);
        }  
        return $this->render('update', [
            'model' => $model,
            'categoryData'=>$categoryData,
            'channelData'=>$channelData,
            'languageData'=>$languageData,
            'searchData' => $searchData,
    
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

    public function actionBannerReference($id)
    {
        $reference_id = isset($_GET['reference_id']) ?  $_GET['reference_id'] :  0;
        $bannerType =  isset($id) ?  $id :  0;
        if($bannerType==1){
             $modelCategory = new LiveTv();
             $resultCategory = $modelCategory->find()->select(['id','name'])->where(['and',['status'=>LiveTv::STATUS_ACTIVE]])->all();
             $searchData = ArrayHelper::map($resultCategory,'id','name');
             if (!empty($resultCategory)) {
                echo "<option value=''>Select Sub Category </option>";
                foreach ($resultCategory as $result) {
                    $selectID = ($result->id ==$reference_id) ? 'selected':'';
                    echo "<option value='" . $result->id . "' ".$selectID.">" . $result->name.  "</option>";
                }
            } else {
                echo "<option value=''> No Result </option>";
            }
        }else if($bannerType==2){
             $modelCategory = new TvShow();
             $resultCategory = $modelCategory->find()->select(['id','name'])->where(['and',['status'=>TvShow::STATUS_ACTIVE]])->all();
             $searchData = ArrayHelper::map($resultCategory,'id','name');
             if (!empty($resultCategory)) {
                echo "<option value=''>Select Sub Category </option>";
                foreach ($resultCategory as $result) {
                    $selectID = ($result->id ==$reference_id) ? 'selected':'';
                    echo "<option value='" . $result->id . "' ".$selectID.">" . $result->name.  "</option>";
                }
            } else {
                echo "<option value=''> No Result </option>";
            }

        }else if($bannerType==3){
             $modelCategory = new TvShowEpisode();
             $resultCategory = $modelCategory->find()->select(['id','name','tv_show_id'])->where(['and',['status'=>TvShowEpisode::STATUS_ACTIVE]])->all();
             $searchData = ArrayHelper::map($resultCategory,'id','name');
             if (!empty($resultCategory)) {
                echo "<option value=''>Select Sub Category </option>";
                foreach ($resultCategory as $result) {
                    $selectID = ($result->id ==$reference_id) ? 'selected':'';
                    // echo "<option value='" . $result->id . "' ".$selectID.">" . $result->name.  "</option>";
                    $showName =  $this->getShowName($result->tv_show_id);
                    $finalName =  $result->name .' ('.$showName.')';
                    echo "<option value='" . $result->id . "' ".$selectID.">" . $finalName.  "</option>";
                }
            } else {
                echo "<option value=''> No Result </option>";
            }
       }

    }

    public function getShowName($id){
        $modelCategory = new TvShow();
        $resultCategory = $modelCategory->find()->select(['id','name'])->where(['and',['id'=>$id]])->all();
        $searchData = ArrayHelper::map($resultCategory,'id','name');
        if (!empty($resultCategory)) {
        foreach ($resultCategory as $result) {
            return $result->name;
        }
        }
    }

}