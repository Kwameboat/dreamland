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
use common\models\Coupon;
use common\models\Post;
use backend\models\CouponSearch;
use yii\filters\AccessControl;

/**
 * 
 */
class CouponController extends Controller
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
                        'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::COUPON),
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
        $searchModel = new CouponSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $modelBusiness = new Business();
        $resultBusiness = $modelBusiness->find()->select(['id','name'])->andWhere(['<>', 'status', Business::STATUS_DELETED])->all();      
        $businessData = ArrayHelper::map($resultBusiness,'id','name');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'businessData' =>$businessData
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
       
        $model = new Coupon();
      
        $model->scenario = 'create';
        $modelBusiness = new Business();
        $resultBusiness = $modelBusiness->find()->select(['id','name'])->andWhere(['<>', 'status', Business::STATUS_DELETED])->all();      
        $businessData = ArrayHelper::map($resultBusiness,'id','name');

        if ($model->load(Yii::$app->request->post()) ) {
            $modelUser = new User();
            $modelUser->checkPageAccess();
            $model->start_date              = strtotime($model->start_date);
            $model->expiry_date              = strtotime($model->expiry_date.'23:59:59');
            $model->code                =    strtoupper($model->code);
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if($model->validate()){
                if($model->imageFile){
                    
                    $type =     Yii::$app->fileUpload::TYPE_COUPON;
                    $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                    $model->image 		= 	  $files[0]['file']; 
                    
                }

                if($model->save()){

                     // create post
                     $modelPost = new Post();
                     $modelPost->type =  Post::TYPE_NORMAL;
                     $modelPost->post_content_type =  Post::CONTENT_TYPE_COUPON;
                     $modelPost->content_type_reference_id =  $model->id;
                     $modelPost->is_add_to_post =  1;
                     $modelPost->save(false);

                    Yii::$app->session->setFlash('success', "Coupon created successfully");
                    return $this->redirect(['index']);
                }
            }
            
        }

        return $this->render('create', [
            'model' => $model,
            'businessData'=>$businessData
            
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
        $modelBusiness = new Business();
        $resultBusiness = $modelBusiness->find()->select(['id','name'])->andWhere(['<>', 'status', Business::STATUS_DELETED])->all();      
        $businessData = ArrayHelper::map($resultBusiness,'id','name');


        $model = $this->findModel($id);

        $model->scenario = 'update';
       
        //if ($model->load(Yii::$app->request->post()) && $model->save()) {


        //if($model->load(Yii::$app->request->post()) && $model->validate()) {
        if($model->load(Yii::$app->request->post())){
            $modelUser = new User();
            $modelUser->checkPageAccess();
            // echo "<pre>";
            // print_r($model);
            // die;
            $model->start_date              = strtotime($model->start_date);
            $model->expiry_date              = strtotime($model->expiry_date.'23:59:59');
            $model->code                =    strtoupper($model->code);
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if($model->imageFile){
                
                $type =     Yii::$app->fileUpload::TYPE_COUPON;
                $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                $model->image 		= 	  $files[0]['file']; 
                
            }
           
          
            if($model->save(false)){
                Yii::$app->session->setFlash('success', "Coupon updated successfully");
                return $this->redirect(['index']);
            };
                
        }else{
            $model->start_date              = date('Y-m-d',$model->start_date);
            $model->expiry_date              = date('Y-m-d',$model->expiry_date);
        }
       
        return $this->render('update', [
            'model' => $model,
            'businessData'=>$businessData
       
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
        $userModel->status =  Coupon::STATUS_DELETED;
        if($userModel->save(false)){

            Yii::$app->session->setFlash('success', "Coupon deleted successfully");

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
        if (($model = Coupon::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}