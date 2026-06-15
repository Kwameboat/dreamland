<?php
namespace backend\controllers;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\PromotionalAd;
use common\models\Category;
use common\models\PromotionalAdCategory;
use common\models\Country;
use yii\data\ActiveDataProvider;
use yii\imagine\Image;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;
use yii\filters\AccessControl;
use app\models\User;

/**
 * 
 */
class PromotionalAdController extends Controller
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
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ]
        ];
    }

    /**
     * Lists all  models.
     * @return mixed
     */
    public function actionIndex()
    {
        
        $model = new PromotionalAd();
        $query = $model->find()
        ->where(['<>','status',PromotionalAd::STATUS_DELETED]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);


        return $this->render('index', [
            'model' => $model,
            'dataProvider' => $dataProvider,
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
       
        $model = new PromotionalAd();
        $modelPromotionalAdCategory = new PromotionalAdCategory();
        $modelCategory = new Category();
        $modelCountry = new Country();

        $countryDataList= $modelCountry->getCountryDropdown();
        
        //$countryDataList = ArrayHelper::map($modelCountry->getAll(), 'id', 'name');
        $mainCategoryData = ArrayHelper::map($modelCategory->getMainCategory(),'id','name');
      
        $model->scenario = 'create';

        if ($model->load(Yii::$app->request->post()) ) {
            $modelUser = new User();
            $modelUser->checkPageAccess();
            
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            $model->videoFile = UploadedFile::getInstance($model, 'videoFile');
            

            if($model->validate()){
            
            
                if($model->imageFile){
                    
                    $type  =  Yii::$app->fileUpload::TYPE_PROMOTIONAL_AD;
                    $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                    $model->image 		= 	  $files[0]['file']; 
                }
                if($model->videoFile  && $model->ad_type== PromotionalAd::AD_TYPE_VIDEO){
                    $type =  Yii::$app->fileUpload::TYPE_PROMOTIONAL_AD;
                    $files = Yii::$app->fileUpload->uploadFile($model->videoFile,$type,false);
                    $model->video 		= 	  $files[0]['file']; 
                }

                $model->start_date = strtotime($model->start_date);
                $model->end_date = strtotime($model->end_date.' 23:59:59');
            
                if($model->save(false)){

                    $modelPromotionalAdCategory->updatePromotionalAdCategory($model->id,$model->category_id);
            
                    Yii::$app->session->setFlash('success', "Promotional ad created successfully");
                    return $this->redirect(['index']);
                }
            }
        }
        return $this->render('create', [
            'model' => $model,
            'mainCategoryData'=>$mainCategoryData,
            'countryDataList' =>$countryDataList
            
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
        $model->scenario = 'update';
        
        $modelPromotionalAdCategory = new PromotionalAdCategory();
        $modelCategory = new Category();
        $modelCountry = new Country();
        $countryDataList= $modelCountry->getCountryDropdown();
        $mainCategoryData = ArrayHelper::map($modelCategory->getMainCategory(),'id','name');



        if ($model->load(Yii::$app->request->post()) ) {
            $modelUser = new User();
            $modelUser->checkPageAccess();
            
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            $model->videoFile = UploadedFile::getInstance($model, 'videoFile');
            

            if($model->validate()){
            
            
                if($model->imageFile){
                    $type =  Yii::$app->fileUpload::TYPE_PROMOTIONAL_AD;
                    $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                    $model->image 		= 	  $files[0]['file']; 
                }
                if($model->videoFile  && $model->ad_type== PromotionalAd::AD_TYPE_VIDEO){
                    
                    $type =   Yii::$app->fileUpload::TYPE_PROMOTIONAL_AD;
                    $files = Yii::$app->fileUpload->uploadFile($model->videoFile,$type,false);
                    $model->video 		= 	  $files[0]['file'];
                }

                $model->start_date = strtotime($model->start_date);
                $model->end_date = strtotime($model->end_date.' 23:59:59');
            
                if($model->save(false)){

                    $modelPromotionalAdCategory->updatePromotionalAdCategory($model->id,$model->category_id);
            
                    Yii::$app->session->setFlash('success', "Promotional ad updated successfully");
                    return $this->redirect(['index']);
                }
            }
        }else{
            
            $model->start_date = date('Y-m-d' ,$model->start_date);
            $model->end_date = date('Y-m-d' ,$model->end_date);

            $selectedIds=[];
            foreach($model->promotionalAdCategory as $result){
                $selectedIds[]=$result->category_id;
            }

            $model->category_id = $selectedIds;

        }
    
      
        return $this->render('update', [
            'model' => $model,
            'mainCategoryData'=>$mainCategoryData,
            'countryDataList' =>$countryDataList
    
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

            Yii::$app->session->setFlash('success', "Banner deleted successfully");

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
        if (($model = PromotionalAd::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
