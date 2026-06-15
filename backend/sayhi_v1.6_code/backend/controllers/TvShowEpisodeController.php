<?php

namespace backend\controllers;

use Yii;
use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\TvShow;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;
use common\models\TvShowEpisode;
use backend\models\TvShowSearch;
use backend\models\TvShowEpisodeSearch;
/**
 * 
 */
class TvShowEpisodeController extends Controller
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
        $searchModel = new TvShowEpisodeSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        // print_r($dataProvider);
        $modelTvShow = new TvShow();
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
        $searchModel = new TvShowEpisodeSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->andWhere('tv_show_episode.tv_show_id = '.$id);

        $modelTvShow = new TvShow();
        $resultTvShow = $modelTvShow->find()->select(['id','name'])->where('tv_show.id = '.$id)->one();
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
       
        $model = new TvShowEpisode();
      
        $model->scenario = 'create';

        $modelTvShow = new TvShow();
        $resultTvShow = $modelTvShow->find()->select(['id','name'])->where(['<>','status',TvShow::STATUS_DELETED])->all();
        $tvShowData = ArrayHelper::map($resultTvShow,'id','name');
    //    print_r($channelData);
    //     die("jdk");
        if ($model->load(Yii::$app->request->post()) ) {
            $model->created_at              = strtotime($model->created_at);
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            $model->videoFile = UploadedFile::getInstance($model, 'videoFile');
            if($model->validate()){
                if($model->imageFile){
                    
                    $type =  Yii::$app->fileUpload::TYPE_TV_SHOW_EPISODE;
                    $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                    $model->image 		= 	  $files[0]['file']; 
                    
                }
                if($model->file_source==TvShowEpisode::FILE_SOURCE_MANUAL_UPLOAD){
                    if($model->videoFile){
                        
                        $type =  Yii::$app->fileUpload::TYPE_TV_SHOW_EPISODE;
                        $files = Yii::$app->fileUpload->uploadFile($model->videoFile,$type,false);
                        $model->video 		= 	  $files[0]['file']; 
                        
                    }
                }

                if($model->save()){
                    Yii::$app->session->setFlash('success', "Tv Show Episode created successfully");
                    return $this->redirect(['tv-show/view', 'id' => @$model->tv_show_id]);
                    // return Yii::$app->response->redirect(['user/index', 'id' => $model->tv_show_id])->send();

                    // $this->redirect(\Yii::$app->urlManager->createUrl(["tv-show"]));
                }
            }
           // print_r($model->errors);
            
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
        if (($model = TvShowEpisode::findOne($id)) !== null) {
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

        $modelTvShow = new TvShow();
        $resultTvShow = $modelTvShow->find()->select(['id','name'])->where(['<>','status',TvShow::STATUS_DELETED])->all();
        $tvShowData = ArrayHelper::map($resultTvShow,'id','name');
       
        if ($model->load(Yii::$app->request->post()) ) {
            
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            $model->videoFile = UploadedFile::getInstance($model, 'videoFile');
            if($model->validate()){
                $model->created_at  = strtotime($model->created_at);
                if($model->imageFile){
                    
                    $type =   Yii::$app->fileUpload::TYPE_TV_SHOW_EPISODE;
                    $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                    $model->image 		= 	  $files[0]['file']; 
                    
                }
                if($model->file_source==TvShowEpisode::FILE_SOURCE_MANUAL_UPLOAD){
                    if($model->videoFile){
                        
                        $type =  Yii::$app->fileUpload::TYPE_TV_SHOW_EPISODE;
                        $files = Yii::$app->fileUpload->uploadFile($model->videoFile,$type,false);
                        $model->video 		= 	  $files[0]['file']; 
                        
                    }
                }
                if($model->save()){
                    Yii::$app->session->setFlash('success', "Tv Show updated data successfully");
                    // return $this->redirect(['index']);
                    return $this->goBack(Yii::$app->request->referrer);
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