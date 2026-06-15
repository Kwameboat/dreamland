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
use common\models\Story;
use backend\models\StorySearch;
use common\models\ReportedStory;
use common\models\User;
use yii\filters\AccessControl;

/**
 * 
 */
class StoryController extends Controller
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
                        'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::STORY),
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
        
        $searchModel = new StorySearch();
        $modelCategory = new Category();
        $modelStory = new Story();
        $modelUser = new User();
        $resultUser = $modelUser->find()->select(['id','username'])->where(['role' => User::ROLE_CUSTOMER])->andWhere(['<>', 'status', User::STATUS_DELETED])->all(); 
        $userData = ArrayHelper::map($resultUser,'id','username');

        $resultCategory = $modelStory->getFilter();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'mainCategoryData'=> $resultCategory,
            'userData' => $userData
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
                    
                    $imageType =  Yii::$app->fileUpload::TYPE_REEL_AUDIO;
                    $files = Yii::$app->fileUpload->uploadFile($model->audioFile,$imageType,false);
                    $model->audio 		= 	  $files[0]['file']; 

                }
                if($model->imageFile){
                    $imageType = Yii::$app->fileUpload::TYPE_REEL_AUDIO;
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
            $model->audioFile = UploadedFile::getInstance($model, 'audioFile');
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            
            if($model->validate()){
                if($model->audioFile){
                        
                    $imageType =     Yii::$app->fileUpload::TYPE_REEL_AUDIO;
                    $files = Yii::$app->fileUpload->uploadFile($model->audioFile,$imageType,false);
                    $model->audio 		= 	  $files[0]['file']; 
    
                }
                if($model->imageFile){
                    $imageType =    Yii::$app->fileUpload::TYPE_REEL_AUDIO;
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
        return $this->render('post-reels', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
            
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

    /**
     * Displays 
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionReportedStory()
    {

        $searchModel = new StorySearch();
        $modelUser = new User();
        

        $resultUser = $modelUser->find()->select(['id','username'])->where(['role' => User::ROLE_CUSTOMER])->andWhere(['<>', 'status', User::STATUS_DELETED])->all();
       
        $userData = ArrayHelper::map($resultUser,'id','username');
        $searchModel = new StorySearch();
        $dataProvider = $searchModel->searchReportedStory(Yii::$app->request->queryParams);

        return $this->render('reported-story', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'userData'=>$userData

        ]);
    }

    public function actionViewReportedStory($id)
    {
        $model = $this->findModelStory($id);

        return $this->render('view-reported-story', [
            'model' => $model,

        ]);
    }


    public function actionReportedStoryAction($id, $type)
    {
        $modelUser = new User();
        $modelUser->checkPageAccess();
        $modelReportedPost = new ReportedStory();
        $model = $this->findModelStory($id);
        // echo "hello";
        // exit;
        if($type=='cancel'){
           
            $currentTime = time();
            $modelReportedPost->updateAll(['status' => ReportedStory::STATUS_REJECTED,'resolved_at'=>$currentTime], [ 'story_id' => $id,'status'=> ReportedStory::STATUS_PENDING]);
            Yii::$app->session->setFlash('success', "Reported request cancelled successfully");
                return $this->redirect(['reported-story']);
        }else if($type=='block'){
            
            $currentTime = time();
            $modelReportedPost->updateAll(['status' => ReportedStory::STATUS_ACEPTED,'resolved_at'=>$currentTime], [ 'story_id' => $id,'status'=> ReportedStory::STATUS_PENDING]);
            
            $model->status = Story::STATUS_BLOCKED;
            if ($model->save(false)) {
                Yii::$app->session->setFlash('success', "Story blocked successfully");
                return $this->redirect(['reported-story']);
            }
        }
       
        
        
    }


    protected function findModelStory($id)
    {
        if (($model = Story::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
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
