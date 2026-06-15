<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
//use common\models\Category;
//use backend\models\CategorySearch;
use app\models\User;
use backend\models\Package;
use common\models\PromotionalBanner;
use yii\data\ActiveDataProvider;
use yii\imagine\Image;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;
use yii\filters\AccessControl;

/**
 * 
 */
class PackageController extends Controller
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
                        'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::PACKAGE),
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
        
        $model = new Package();
        $query = $model->find()
        ->where(['<>','status',Package::STATUS_DELETED]);

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
     * Creates
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
       
        $model = new Package();
        
        if ($model->load(Yii::$app->request->post())) {
            $modelUser = new User();
            $modelUser->checkPageAccess();
            
            if($model->save()){
                Yii::$app->session->setFlash('success', "Package created successfully");
                return $this->redirect(['index']);
            
            }
               
        }
        return $this->render('create', [
            'model' => $model,
         
            
        ]);
    }

    public function actionUpdate($id)
    {
        
        
        $model = $this->findModel($id);
        
        if ($model->load(Yii::$app->request->post()) ) {
            $modelUser = new User();
            $modelUser->checkPageAccess();
            
            if( $model->save()){

                Yii::$app->session->setFlash('success', "Package updated successfully");
                return $this->redirect(['index']);
            }
           
        }
    
        return $this->render('update', [
            'model' => $model,
            
    
        ]);
    
    }


    public function actionDelete($id)
    {
        $modelUser = new User();
        $modelUser->checkPageAccess();
        
        $model= $this->findModel($id);
        $model->status =  $model::STATUS_DELETED;
        if($model->save(false)){

            Yii::$app->session->setFlash('success', "Package deleted successfully");

            return $this->redirect(['index']);
        }
        
    }

    protected function findModel($id)
    {
        if (($model = Package::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
