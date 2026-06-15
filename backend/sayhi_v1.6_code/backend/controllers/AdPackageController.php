<?php

namespace backend\controllers;

use backend\models\Ad;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
//use common\models\Category;
//use backend\models\CategorySearch;

use backend\models\AdPackage;
use common\models\PromotionalBanner;
use yii\data\ActiveDataProvider;
use yii\imagine\Image;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;
use yii\filters\AccessControl;

/**
 * 
 */
class AdPackageController extends Controller
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
        
        $model = new AdPackage();
        $query = $model->find()
        ->where(['<>','status',AdPackage::STATUS_DELETED]);

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
       
        $model = new AdPackage();
        $modelPromotionalBanner = new PromotionalBanner();
        
        $promotionalBannerData = ArrayHelper::map($modelPromotionalBanner->getAllPromotionalBanner(),'id','name');
       
        if ($model->load(Yii::$app->request->post())) {
            if($model->save()){
                Yii::$app->session->setFlash('success', "Package created successfully");
                return $this->redirect(['index']);
            
            }
               
        }else{

            $model->type=$model::TYPE_ORDINARY;
        }
        return $this->render('create', [
            'model' => $model,
            'promotionalBannerData'=>$promotionalBannerData
            
        ]);
    }

    public function actionUpdate($id)
    {
        $modelPromotionalBanner = new PromotionalBanner();
        $promotionalBannerData = ArrayHelper::map($modelPromotionalBanner->getAllPromotionalBanner(),'id','name');
        $model = $this->findModel($id);
        
        if ($model->load(Yii::$app->request->post()) ) {
            
            if($model->type==$model::TYPE_ORDINARY){
                $model->promotional_banner_id=null;
            }
            if( $model->save()){

                Yii::$app->session->setFlash('success', "Package updated successfully");
                return $this->redirect(['index']);
            }
           
        }
    
        return $this->render('update', [
            'model' => $model,
            'promotionalBannerData'=>$promotionalBannerData
    
        ]);
    
    }


    public function actionDelete($id)
    {
        $model= $this->findModel($id);
        $model->status =  $model::STATUS_DELETED;
        if($model->save(false)){

            Yii::$app->session->setFlash('success', "Package deleted successfully");

            return $this->redirect(['index']);
        }
        
    }

    protected function findModel($id)
    {
        if (($model = AdPackage::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
