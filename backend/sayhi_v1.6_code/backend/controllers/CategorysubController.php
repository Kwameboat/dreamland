<?php

namespace backend\controllers;

use Yii;
//use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\Category;
use backend\models\CategorySearch;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;

/**
 * 
 */
class CategorysubController extends Controller
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
        $searchModel = new CategorySearch();
        $mainCategoryData = ArrayHelper::map($searchModel->getMainCategory(),'id','name');

        $dataProvider = $searchModel->searchSubCategory(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'mainCategoryData'=> $mainCategoryData
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
        $model = new Category();
        $mainCategoryData = ArrayHelper::map($model->getMainCategory(),'id','name');
       
        $model->scenario = 'createSubCategory';
        $model->level = $model::LEVEL_SUB;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            
            Yii::$app->session->setFlash('success', "Category created successfully");
            return $this->redirect(['index']);
            
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
        $model->scenario = 'updateSubCategory';
        $mainCategoryData = ArrayHelper::map($model->getMainCategory(),'id','name');
       
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', "Category updated successfully");
            return $this->redirect(['index']);
            
                
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
        $userModel= $this->findModel($id);
        $userModel->status =  Category::STATUS_DELETED;
        if($userModel->save(false)){

            Yii::$app->session->setFlash('success', "Category deleted successfully");

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
        if (($model = Category::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
