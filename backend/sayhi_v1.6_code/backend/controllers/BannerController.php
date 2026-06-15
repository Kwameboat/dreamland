<?php
namespace backend\controllers;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\Banner;
use yii\data\ActiveDataProvider;
use yii\imagine\Image;
use yii\web\UploadedFile;

/**
 * 
 */
class BannerController extends Controller
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
        
        $model = new Banner();
        $query = $model->find()
        ->where(['<>','status',Banner::STATUS_DELETED]);

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
       
        $model = new Banner();
      
        $model->scenario = 'create';

        if ($model->load(Yii::$app->request->post()) ) {
            
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if($model->validate()){
            
            
                if($model->imageFile){
                    
                    $microtime 			= 	(microtime(true)*10000);
                    $uniqueimage		=	$microtime.'_'.date("Ymd_His").'_'.substr(md5($microtime),0,10); 
                    $imageName 			=	$uniqueimage;
                    $model->image 		= 	$imageName.'.'.$model->imageFile->extension; 
                    $imagePath 			=	Yii::$app->params['pathUploadBanner'] ."/".$model->image;
                    $imagePathThumb 	=	Yii::$app->params['pathUploadBannerThumb'] ."/".$model->image;
                    $model->imageFile->saveAs($imagePath,false);


                    Image::thumbnail($imagePath, 200, 200)
                        ->save($imagePathThumb, ['quality' => 100]);

                        
                
                }
                
                if($model->save(false)){
            
                Yii::$app->session->setFlash('success', "Banner created successfully");
                return $this->redirect(['index']);
                }
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
        if ($model->load(Yii::$app->request->post())) {
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            
            if($model->validate()){
                if($model->imageFile){
                        
                    $microtime 			= 	(microtime(true)*10000);
                    $uniqueimage		=	$microtime.'_'.date("Ymd_His").'_'.substr(md5($microtime),0,10); 
                    $imageName 			=	$uniqueimage;
                    $model->image 		= 	$imageName.'.'.$model->imageFile->extension; 
                    $imagePath 			=	Yii::$app->params['pathUploadBanner'] ."/".$model->image;
                    $imagePathThumb 	=	Yii::$app->params['pathUploadBannerThumb'] ."/".$model->image;
                    $model->imageFile->saveAs($imagePath,false);
    
    
                    Image::thumbnail($imagePath, 200, 200)
                        ->save($imagePathThumb, ['quality' => 100]);
    
                }
                
            
                if($model->save()){
                    Yii::$app->session->setFlash('success', "Banner updated successfully");
                    return $this->redirect(['index']);
                };
                
            }
        }
    
      
        return $this->render('update', [
            'model' => $model
    
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
        if (($model = Banner::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
