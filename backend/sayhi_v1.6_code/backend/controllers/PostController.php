<?php
namespace backend\controllers;

use api\modules\v1\models\MentionUser;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\Post;
use backend\models\PostSearch;
use common\models\PostGallary;

use common\models\ReportedPost;
use yii\data\ActiveDataProvider;
use yii\imagine\Image;
use yii\web\UploadedFile;
//use common\models\Category;
use yii\helpers\ArrayHelper;
use app\models\User;
use yii\filters\AccessControl;

/**
 * 
 */
class PostController extends Controller
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
                        'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::POST),
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
        
        $searchModel = new PostSearch();
        $modelUser = new User();
        

        $resultUser = $modelUser->find()->select(['id','username'])->where(['role' => User::ROLE_CUSTOMER])->andWhere(['<>', 'status', User::STATUS_DELETED])->all();
       
        $userData = ArrayHelper::map($resultUser,'id','username');
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'userData'=>$userData
         
        ]);
        
      
    }

     /**
     * Displays 
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionReportedPost()
    {

        $searchModel = new PostSearch();
        $modelUser = new User();
        

        $resultUser = $modelUser->find()->select(['id','username'])->where(['role' => User::ROLE_CUSTOMER])->andWhere(['<>', 'status', User::STATUS_DELETED])->all();
       
        $userData = ArrayHelper::map($resultUser,'id','username');
        $searchModel = new PostSearch();
        $dataProvider = $searchModel->searchReportedPost(Yii::$app->request->queryParams);

        return $this->render('reported-post', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'userData'=>$userData

        ]);
    }

    public function actionViewReportedPost($id)
    {
        $model = $this->findModel($id);

        return $this->render('view-reported-post', [
            'model' => $model,

        ]);
    }

    

    
    public function actionReportedPostAction($id, $type)
    {
        $modelUser = new User();
        $modelUser->checkPageAccess();
        $modelReportedPost = new ReportedPost();
        $model = $this->findModel($id);
        if($type=='cancel'){
           
            $currentTime = time();
            $modelReportedPost->updateAll(['status' => ReportedPost::STATUS_REJECTED,'resolved_at'=>$currentTime], [ 'post_id' => $id,'status'=> ReportedPost::STATUS_PENDING]);
            Yii::$app->session->setFlash('success', "Reported request cancelled successfully");
                return $this->redirect(['reported-post']);
        }else if($type=='block'){
            
            $currentTime = time();
            $modelReportedPost->updateAll(['status' => ReportedPost::STATUS_ACEPTED,'resolved_at'=>$currentTime], [ 'post_id' => $id,'status'=> ReportedPost::STATUS_PENDING]);
            
            $model->status = $model::STATUS_BLOCKED;
            if ($model->save(false)) {
                Yii::$app->session->setFlash('success', "Post blocked successfully");
                return $this->redirect(['reported-post']);
            }
        }
       
        
        
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

    public function actionCreate()
    {
        
        $model =new Post();
     
        if ($model->load(Yii::$app->request->post())) {
            $modelUser = new User();
            $modelUser->checkPageAccess();
            $model->imageFile = UploadedFile::getInstances($model, 'imageFile');
            if($model->validate()){
                
                $images =[];
                foreach ($model->imageFile as $file) {
                    
                   

                    $imageType =     Yii::$app->fileUpload::TYPE_POST;
                    $files       = Yii::$app->fileUpload->uploadFile($file,$imageType,false);
                    $images[]	= 	  $files[0]; 
                }
                $model->user_id =   Yii::$app->user->identity->id;
                $model->type = Post::TYPE_NORMAL;

                if(count($images)>0){
                    $model->post_content_type =2;
                }else{
                    $model->post_content_type =1;
                }
                if($model->save()){
                    if(count($images)>0){
                        $modelPostGallary = new PostGallary();
                        $modelPostGallary->updateGallary($model->id, $images);
                    }
                    
                    Yii::$app->session->setFlash('success', "Post created successfully");
                    return $this->redirect(['index']);
                };
                
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
        $modelCategory = new Category();
        $mainCategoryData = ArrayHelper::map($modelCategory->getMainCategory(),'id','name');

        $preAudio = $model->audio;

        if ($model->load(Yii::$app->request->post())) {
            $modelUser = new User();
            $modelUser->checkPageAccess();
            $model->audioFile = UploadedFile::getInstance($model, 'audioFile');
            
            if($model->validate()){
                if($model->audioFile){
                        
                    $microtime 			= 	(microtime(true)*10000);
                    $uniqueimage		=	$microtime.'_'.date("Ymd_His").'_'.substr(md5($microtime),0,10); 
                    
                    $imageName 			=	$uniqueimage.'.'.$model->audioFile->extension;
                    $model->audio 		= 	$imageName; 
                    
                    $s3 = Yii::$app->get('s3');
                    $imagePath = $model->audioFile->tempName;
                    $result = $s3->upload('./'.Yii::$app->params['pathUploadAudioFolder'].'/'.$imageName, $imagePath);
                    $oldAudioPath =  './'.Yii::$app->params['pathUploadAudioFolder'].'/'.$preAudio;

                     $res = $s3->commands()->delete('./'.Yii::$app->params['pathUploadAudioFolder'].'/'.$preAudio)->execute(); /// delere previous
                    
                  
                    /*$imagePath 			=	Yii::$app->params['pathUploadBanner'] ."/".$model->image;
                    $imagePathThumb 	=	Yii::$app->params['pathUploadBannerThumb'] ."/".$model->image;
                    $model->audioFile->saveAs($imagePath,false);
    
    
                    Image::thumbnail($imagePath, 200, 200)
                        ->save($imagePathThumb, ['quality' => 100]);*/
    
                }
                
            
                if($model->save()){
                    
                    Yii::$app->session->setFlash('success', "Post updated successfully");
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


            Yii::$app->db->createCommand()->delete('mention_user', ['post_id'=>$id])->execute();

            Yii::$app->session->setFlash('success', "Post deleted successfully");

            return $this->redirect(['index']);
        }
        
    }


    public function actionUpdateStatus($id,$type)
    {
        $modelUser = new User();
        $modelUser->checkPageAccess();
        $model= $this->findModel($id);
        $msg = '';
        if($type=='active'){
            $model->status =  $model::STATUS_ACTIVE;
            $msg = 'Post reactive successfully';
        }else if($type=='block'){
            $model->status =  $model::STATUS_BLOCKED;
            $msg = 'Post blocked successfully';
        }
        
        if($model->save(false)){

            Yii::$app->session->setFlash('success', $msg);

            return $this->redirect(['view','id'=>$id]);
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
        if (($model = Post::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
