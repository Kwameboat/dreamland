<?php
namespace backend\controllers;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\Audio;
use backend\models\AudioSearch;
use yii\data\ActiveDataProvider;
use yii\imagine\Image;
use yii\web\UploadedFile;
use common\models\Category;
use yii\helpers\ArrayHelper;
use common\models\Post;
use backend\models\PostSearch;
use common\models\User;
use yii\filters\AccessControl;


/**
 * 
 */
class AudioController extends Controller
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
                        'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::REEL),
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
        
        $searchModel = new AudioSearch();
        $modelCategory = new Category();
        
        $resultCategory = $modelCategory->find()->select(['id','name'])->where(['type'=>Category::TYPE_REEL_AUDIO])->andWhere(['<>', 'status', Category::STATUS_DELETED])->all();
        $mainCategoryData = ArrayHelper::map($resultCategory,'id','name');
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'mainCategoryData'=> $mainCategoryData
        ]);
        
      
    }

    /**
     * Displays 
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
     
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
       
        $model = new Audio();
        $modelCategory = new Category();
       
        
        $resultCategory = $modelCategory->find()->select(['id','name'])->where(['type'=>Category::TYPE_REEL_AUDIO])->andWhere(['<>', 'status', Category::STATUS_DELETED])->all();
        $mainCategoryData = ArrayHelper::map($resultCategory,'id','name');

        
        
      
        $model->scenario = 'create';

        if ($model->load(Yii::$app->request->post()) ) {
            
            $model->audioFile = UploadedFile::getInstance($model, 'audioFile');
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');

            
            if($model->validate()){
            
            
                if($model->audioFile){
                    
                    $imageType =     Yii::$app->fileUpload::TYPE_REEL_AUDIO;
                    $files = Yii::$app->fileUpload->uploadFile($model->audioFile,$imageType,false);
                    $model->audio 		= 	  $files[0]['file']; 

                }
                if($model->imageFile){
                    $imageType =     Yii::$app->fileUpload::TYPE_REEL_AUDIO;
                    $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$imageType,false);
                    $model->image 		= 	  $files[0]['file']; 

                }
                
                
                if($model->save(false)){
            
                Yii::$app->session->setFlash('success', "Audio created successfully");
                return $this->redirect(['index']);
                }
            }
        }
        return $this->render('create', [
            'model' => $model,
            'mainCategoryData'=>$mainCategoryData
            
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
        $modelCategory = new Category();


        $resultCategory = $modelCategory->find()->select(['id','name'])->where(['type'=>Category::TYPE_REEL_AUDIO])->andWhere(['<>', 'status', Category::STATUS_DELETED])->all();
        $mainCategoryData = ArrayHelper::map($resultCategory,'id','name');


        $preAudio = $model->audio;
        $preImage = $model->image;

        if ($model->load(Yii::$app->request->post())) {
            $modelUser = new User();
            $modelUser->checkPageAccess();
    
            $model->audioFile = UploadedFile::getInstance($model, 'audioFile');
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            
            if($model->validate()){
                if($model->audioFile){
                        
                    $imageType =     Yii::$app->fileUpload::TYPE_REEL_AUDIO;
                    $files = Yii::$app->fileUpload->uploadFile($model->audioFile,$imageType,false);
                    $model->audio 		= 	  $files[0]['file']; 
    
                }
                if($model->imageFile){
                    $imageType =     Yii::$app->fileUpload::TYPE_REEL_AUDIO;
                    $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$imageType,false);
                    $model->image 		= 	  $files[0]['file']; 
                  
                }
                
            
                if($model->save()){
                    
                    Yii::$app->session->setFlash('success', "Audio updated successfully");
                    return $this->redirect(['index']);
                };
                
            }
        }
    
      
        return $this->render('update', [
            'model' => $model,
            'mainCategoryData'=>$mainCategoryData
    
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

            Yii::$app->session->setFlash('success', "Audio deleted successfully");

            return $this->redirect(['index']);
        }
        
    }

    /**
     * User Post Lists all  reels.
     * @return mixed
     */
    public function actionPostReels()
    {
        
        $searchModel = new PostSearch();

        $dataProvider = $searchModel->searchReelPost(Yii::$app->request->queryParams);
        $modelUser = new User();
        $resultUser = $modelUser->find()->select(['id','username'])->where(['role' => User::ROLE_CUSTOMER])->andWhere(['status'=>User::STATUS_ACTIVE])->all(); 
        $userData = ArrayHelper::map($resultUser,'id','username');
        return $this->render('post-reels', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'userData' => $userData
            
        ]);
        
      
    }

        /**
     * User Post Lists all  reels.
     * @return mixed
     */
    public function actionAudioDetails($audio_id)
    {
        
        $model  = $this->findModel($audio_id);
        
        return $this->render('audio-view', [
            'model' =>   $model
        ]);
        
      
    }

    public function actionReelView($id,$audio_id=null)
    {
       
        $modelPost = new Post();
        $result = $modelPost->find()->where(['id'=>$id])->one();
        if($audio_id==null){
            return $this->render('reel-view', [
                'postResult' => $result
            ]);
        }else{
            $model  = $this->findModel($audio_id);
            return $this->render('reel-view', [
                'model' =>   $model,
                'postResult' => $result
            ]);
        }
        
    }

    // delete post reels
    public function actionReelsDelete($id)
    {
        $modelUser = new User();
        $modelPost = new Post();
        $modelUser->checkPageAccess();
        $model= Post::find()->where(['id' => $id])->one();
        $model->status =  Post::STATUS_DELETED;
        if($model->save(false)){

            Yii::$app->session->setFlash('success', "Reels deleted successfully");

            return $this->redirect(['audio/post-reels']);
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
        if (($model = Audio::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
