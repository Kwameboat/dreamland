<?php
namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

use common\models\EventCoupon;
use backend\models\EventCouponSearch;
use yii\data\ActiveDataProvider;
use yii\imagine\Image;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;
use app\models\User;
use yii\filters\AccessControl;

/**
 * 
 */
class EventCouponController extends Controller
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
                    'winning' => ['POST'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::EVENT),
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
        
        $searchModel = new EventCouponSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'model' => $searchModel,
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
     * Creates
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {

        $model = new EventCoupon();
        
        $model->scenario= 'create';
        if ($model->load(Yii::$app->request->post())  ) {
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            $preImage = $model->image;
            if($model->validate()){
                if($model->imageFile){
                     $type =     Yii::$app->fileUpload::TYPE_COUPON;
                    $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                    $model->image 		= 	  $files[0]['file']; 
                    
                }
                $model->expiry_date                = strtotime($model->expiry_date.' 23:59:59');
                $model->code                =    strtoupper($model->code);
                
                if( $model->save(false)){
                    Yii::$app->session->setFlash('success', "Coupon created successfully");
                    return $this->redirect(['index']);
                }
            }
          
        }
        
        return $this->render('create', [
            'model' => $model
            
        ]);
    }

    public function actionUpdate($id)
    {
        
        
        $model = $this->findModel($id);
        $model->scenario= 'update';
        
        if ($model->load(Yii::$app->request->post())  ) {
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            $model->code                =    strtoupper($model->code);
            $preImage = $model->image;
            if($model->validate()){
                if($model->imageFile){
                    $type =     Yii::$app->fileUpload::TYPE_COUPON;
                    $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                    
                    $model->image 		= 	  $files[0]['file']; 

                }
                $model->expiry_date                = strtotime($model->expiry_date.' 23:59:59');
                
                if( $model->save(false)){
                    
                    Yii::$app->session->setFlash('success', "Coupon updated successfully");
                    return $this->redirect(['index']);
                }
            }
           
           
        }else{
            $model->expiry_date              = date('Y-m-d',$model->expiry_date);
        }
        return $this->render('update', [
            'model' => $model
    
        ]);
    
    }



    public function actionDelete($id)
    {
        $modelUser = new User();
        $modelUser->checkPageAccess();
        $model= $this->findModel($id);
        $model->status =  $model::STATUS_DELETED;
        if($model->save(false)){

            Yii::$app->session->setFlash('success', "Coupon deleted successfully");

            return $this->redirect(['index']);
        }
        
    }

    protected function findModel($id)
    {
        if (($model = EventCoupon::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
