<?php

namespace backend\controllers;

use Yii;
use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\LiveTv;
use backend\models\LiveTvSearch;
use yii\web\UploadedFile;
use common\models\FileUpload;
use common\models\LiveTvCategory;
use yii\helpers\ArrayHelper;
use common\models\Poll;
use common\models\Category;
use backend\models\PollSearch;
use common\models\Organization;
use backend\models\PollQuestionSearch;
use backend\models\PollQuestionOptionSearch;
use common\models\PollQuestion;
use common\models\PollQuestionOption;
/**
 * 
 */
class PollQuestionOptionController extends Controller
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
        $searchModel = new PollQuestionOptionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $modeldata = new PollQuestion();
        $resultData = $modeldata->find()->select(['id','title'])->andWhere(['and', 'status', PollQuestion::STATUS_ACTIVE])->all(); 
       
        $data = ArrayHelper::map($resultData,'id','title');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'categoryData' =>$data,
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
        
        $model = new PollQuestionOption();
      
        $model->scenario = 'create';
        $modeldata = new Poll();
        $resultData = $modeldata->find()->select(['id','title'])->andWhere(['status'=>Poll::STATUS_ACTIVE])->all(); 
       
        $data = ArrayHelper::map($resultData,'id','title');
        if ($model->load(Yii::$app->request->post()) ) {
            $modelUser = new User();
            $modelUser->checkPageAccess();
            
            if($model->validate()){

                if($model->save()){
                    Yii::$app->session->setFlash('success', "Poll Option created successfully");
                    // return $this->redirect(['index']);
                    return $this->redirect(['poll/view', 'id' => @$model->poll_id]);
                }
            }
            
        }

        return $this->render('create', [
            'model' => $model,
            'categoryData'=>$data,
            
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
        $modeldata = new Poll();
        $resultData = $modeldata->find()->select(['id','title'])->andWhere(['status' => Poll::STATUS_ACTIVE])->all(); 
       
        $data = ArrayHelper::map($resultData,'id','title');


        $model = $this->findModel($id);

        $model->scenario = 'update';

        //if($model->load(Yii::$app->request->post()) && $model->validate()) {
        if($model->load(Yii::$app->request->post())){
            $modelUser = new User();
            $modelUser->checkPageAccess();
         
            if($model->save(false)){
                Yii::$app->session->setFlash('success', "Poll Option updated successfully");
                // return $this->redirect(['index']);
                return $this->redirect(['poll/view', 'id' => @$model->poll_id]);
            }
                
        }
       
        return $this->render('update', [
            'model' => $model,
            'categoryData'=>$data,

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
        $modelUser = new user();
        $modelUser->checkPageAccess();
        $modelUser= $this->findModel($id);
        $modelUser->status =  PollQuestionOption::STATUS_DELETED;
        if($modelUser->save(false)){
            
            Yii::$app->session->setFlash('success', "Poll option deleted successfully");
            $model = $this->findModel($id);
            if(@$model->poll_id){
                return $this->redirect(['poll/view' , 'id'=>@$model->poll_id]);
            }else{
                return $this->redirect(['index']);
            }
            
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
        if (($model = PollQuestionOption::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}