<?php
namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\OrganizationType;
use yii\data\ActiveDataProvider;
use yii\imagine\Image;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;





/**
 * 
 */
class OrginazitionTypeController extends Controller
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
        ];
    }

    /**
     * Lists all  models.
     * @return mixed
     */
    public function actionIndex()
    {
       
        $model = new OrganizationType();
        $query = $model->find()
        ->where(['<>','status',OrganizationType::STATUS_DELETED]);

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
       
        $model = new OrganizationType();
      
        //  $model->scenarios = 'create';
        $modelCategory = new OrganizationType();
        $resultCategory = $modelCategory->find()->select(['id','name'])->andWhere(['<>', 'status', OrganizationType::STATUS_DELETED])->all();
        $categoryData = ArrayHelper::map($resultCategory,'id','name');


        if ($model->load(Yii::$app->request->post()) ) {
            
            if($model->validate()){
               
                if($model->save()){
                    Yii::$app->session->setFlash('success', "Record Created successfully");
                    return $this->redirect(['index']);
                }
            }
        }

        return $this->render('create', [
            'model' => $model,
            'categoryData'=>$categoryData
            
        ]);
    }


    // Update new 
    public function actionUpdate($id)
    {
        $modelCategory = new OrganizationType();
        $resultCategory = $modelCategory->find()->select(['id','name'])->andWhere(['<>', 'status', OrganizationType::STATUS_DELETED])->all();
        $categoryData = ArrayHelper::map($resultCategory,'id','name');


        $model = $this->findModel($id);

        // $model->scenario = 'update';
       
        if($model->load(Yii::$app->request->post())){
            
            if($model->save(false)){
                Yii::$app->session->setFlash('success', " Updated successfully");
                return $this->redirect(['index']);
            }
                
        }
       
        return $this->render('update', [
            'model' => $model,
            'categoryData'=>$categoryData
       
        ]);
    }


    
    
   
    public function actionDelete($id)
    {
        $model= $this->findModel($id);
        $model->status =  $model::STATUS_DELETED;
        if($model->save(false)){

            Yii::$app->session->setFlash('success', "Organization type deleted successfully");

            return $this->redirect(['index']);
        }
        
    }

    protected function findModel($id)
    {
        if (($model = OrganizationType::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
