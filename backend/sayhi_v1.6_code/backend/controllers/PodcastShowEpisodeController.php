<?php

namespace backend\controllers;

use Yii;
//use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\Podcast;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;
use common\models\PodcastShowEpisode;
use common\models\PodcastShow;
use backend\models\PodcastShowEpisodeSearch;
/**
 * 
 */
class PodcastShowEpisodeController extends Controller
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
        $searchModel = new PodcastShowEpisodeSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        // print_r($dataProvider);
        $modelTvShow = new Podcast();
        $resultTvShow = $modelTvShow->find()->select(['id','name'])->all();
        $tvShowData = ArrayHelper::map($resultTvShow,'id','name');
        // print_r($tvShowData);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'tvShowData'=>$tvShowData
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
        $searchModel = new PodcastShowEpisodeSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->andWhere('podcast_show_episode.podcast_show_id = '.$id);

        $modelTvShow = new PodcastShow();
        $resultTvShow = $modelTvShow->find()->select(['id','name'])->where('podcast_show.id = '.$id)->one();
        $tvShowData = ArrayHelper::map($resultTvShow,'id','name');
        $tvShowName=  $modelTvShow->findOne($id);
        // print_r($tvShowName);
        return $this->render('view', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'tvShowData'=>$tvShowName
        ]);
    }

    public function actionViewDetail($id)
    {
        $model  = $this->findModel($id);
        
        return $this->render('view-detail', [
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
       
        $model = new PodcastShowEpisode();
      
        $model->scenario = 'create';

        $modelTvShow = new PodcastShow();
        $resultTvShow = $modelTvShow->find()->select(['id','name'])->where(['status'=>PodcastShow::STATUS_ACTIVE])->all();
        $tvShowData = ArrayHelper::map($resultTvShow,'id','name');
        if ($model->load(Yii::$app->request->post()) ) {
            $model->created_at              = strtotime($model->created_at);
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            $model->audioFile = UploadedFile::getInstance($model, 'audioFile');
            // $model->video = UploadedFile::getInstance($model, 'video');
            if($model->validate()){
                if($model->imageFile){
                    $type =  Yii::$app->fileUpload::TYPE_PODCAST_SHOW;
                    $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                    $model->image 		= 	  $files[0]['file']; 
                    
                }
                if($model->file_source==1){
                    if($model->audioFile){
                        
                        $imageType =   Yii::$app->fileUpload::TYPE_PODCAST_SHOW;
                        $files = Yii::$app->fileUpload->uploadFile($model->audioFile,$imageType,false);
                        $model->audio 		= 	  $files[0]['file']; 
                    }
                }    
                if($model->save()){
                    Yii::$app->session->setFlash('success', "Poscast show episode created successfully");
                    // return $this->redirect(['index']);
                    // $this->redirect(\Yii::$app->urlManager->createUrl(["podcast-show"]));
                    return $this->redirect(['podcast-show/view', 'id' => @$model->podcast_show_id]);
                }
            }
            
        }
        // print_r($channelData);
        // exit("kf;   ");

        return $this->render('create', [
            'model' => $model,
            'tvShowData'=>$tvShowData
            
            
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

            Yii::$app->session->setFlash('success', "Podcast show episode deleted successfully");

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
        if (($model = PodcastShowEpisode::findOne($id)) !== null) {
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
        // $modelCategory = new Category();
        // $resultCategory = $modelCategory->find()->select(['id','name'])->where(['and',['type'=>[3]]])->andWhere(['and', 'status', Category::STATUS_ACTIVE])->all();
        // $categoryData = ArrayHelper::map($resultCategory,'id','name');

        $modelTvShow = new PodcastShow();
        $resultTvShow = $modelTvShow->find()->select(['id','name'])->where(['status'=>PodcastShow::STATUS_ACTIVE])->all();
        $tvShowData = ArrayHelper::map($resultTvShow,'id','name');
       

        if ($model->load(Yii::$app->request->post()) ) {
            
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            $model->audioFile = UploadedFile::getInstance($model, 'audioFile');
            if($model->validate()){
                $model->created_at  = strtotime($model->created_at);
                if($model->audioFile){
                    
                    $imageType =   Yii::$app->fileUpload::TYPE_PODCAST_SHOW;
                    $files = Yii::$app->fileUpload->uploadFile($model->audioFile,$imageType,false);
                    $model->audio 		= 	  $files[0]['file']; 

                }
                if($model->imageFile){
                    
                    $type =  Yii::$app->fileUpload::TYPE_PODCAST_SHOW;
                    $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                    $model->image 		= 	  $files[0]['file']; 
                    
                }
                
                if($model->save()){
                    Yii::$app->session->setFlash('success', "Podcast show episode updated data successfully");
                    // return $this->redirect(['index']);
                    // return $this->goBack(Yii::$app->request->referrer);
                    $previousUrl = Yii::$app->session->get('previousUrl');
                    Yii::$app->session->remove('previousUrl'); // Clear the previousUrl session variable

                    if ($previousUrl !== null) {
                        // Redirect to the stored URL
                        return $this->redirect($previousUrl);
                    } else {
                        // Redirect to a default URL if the previous URL is not available
                        return $this->redirect(['index']); // Replace 'index' with your desired default URL
                    }
                }
            }
            
        }else{
            $model->created_at              = date('Y-m-d',$model->created_at);
        }  
        return $this->render('update', [
            'model' => $model,
            'tvShowData'=>$tvShowData
    
        ]);
    
    }



}