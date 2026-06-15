<?php

namespace backend\controllers;

use Yii;
use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\Category;
use yii\web\UploadedFile;
use common\models\FileUpload;
use common\models\Job;
use common\models\Post;
use common\models\Organization;
use backend\models\JobSearch;
use common\models\City;
use common\models\Country;
use common\models\State;
use yii\helpers\ArrayHelper;
use yii\filters\AccessControl;

/**
 * 
 */
class JobController extends Controller
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
                        'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::JOB),
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
        $searchModel = new JobSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);


        $modelCategory = new Category();
        

        $resultCategory = $modelCategory->find()->select(['id','name'])->andWhere(['<>', 'status', Category::STATUS_DELETED])->andWhere(['type'=>Category::TYPE_JOB_CATEGORY])->all();
       
        $categoryData = ArrayHelper::map($resultCategory,'id','name');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'categoryData' =>$categoryData
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
       
        $model = new Job();
        $modelOrganization = new Organization();
        $model->scenario = 'create';
        $modelCategory = new Category();
        $resultCategory = $modelCategory->find()->select(['id','name'])->andWhere(['type'=>Category::TYPE_JOB_CATEGORY,'status'=> Category::STATUS_ACTIVE])->all();
        $categoryData = ArrayHelper::map($resultCategory,'id','name');
        // organization
        $orgData = $modelOrganization->find()->select(['id','name'])->andWhere(['status'=>Organization::STATUS_ACTIVE])->all();
        $organizationData = ArrayHelper::map($orgData,'id','name');
        // country list 
        $modelCountry = new Country();
        $countryData = $modelCountry->find()->select(['id','name'])->andWhere(['status'=>Country::STATUS_ACTIVE])->all();
        $countryList = ArrayHelper::map($countryData,'id','name');

        if ($model->load(Yii::$app->request->post()) ) {
            $modelUser = new User();
            $modelUser->checkPageAccess();
              if($model->validate()){
                if($model->save()){
                    $modelPost = new Post();
                    $modelPost->type =  Post::TYPE_NORMAL;
                    $modelPost->post_content_type =  Post::CONTENT_TYPE_JOB;
                    $modelPost->content_type_reference_id =  $model->id;
                    $modelPost->is_add_to_post =  1;
                    $modelPost->save();


                    Yii::$app->session->setFlash('success', "Job created successfully");
                    return $this->redirect(['index']);
                }
            }
            
        }

        return $this->render('create', [
            'model' => $model,
            'categoryData'=>$categoryData,
            'organizationData'=>$organizationData,
            'countryList' =>$countryList
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
        $modelCategory = new Category();
        $modelOrganization = new Organization();
        $resultCategory = $modelCategory->find()->select(['id','name'])->andWhere(['type'=>Category::TYPE_JOB_CATEGORY])->andWhere(['<>', 'status', Category::STATUS_DELETED])->all();
        $categoryData = ArrayHelper::map($resultCategory,'id','name');

         // organization
         $orgData = $modelOrganization->find()->select(['id','name'])->andWhere(['status'=>Organization::STATUS_ACTIVE])->all();
         $organizationData = ArrayHelper::map($orgData,'id','name');
         
         $modelCountry = new Country();
         $countryData = $modelCountry->find()->select(['id','name'])->andWhere(['status'=>Country::STATUS_ACTIVE])->all();
         $countryList = ArrayHelper::map($countryData,'id','name');
 
        $model = $this->findModel($id);

        $model->scenario = 'update';
       
        if($model->load(Yii::$app->request->post())){
           
            $modelUser = new User();
            $modelUser->checkPageAccess();
          
            if($model->save(false)){
                Yii::$app->session->setFlash('success', "Job updated successfully");
                return $this->redirect(['index']);
            };
                
        }
       
        return $this->render('update', [
            'model' => $model,
            'categoryData'=>$categoryData,
            'organizationData'=>$organizationData,
            'countryList' =>$countryList
       
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
        $userModel->status =  Job::STATUS_DELETED;
        if($userModel->save(false)){

            Yii::$app->session->setFlash('success', "Job deleted successfully");

            return $this->redirect(['index']);
        }
        
    }

    public function actionStateList($countryId)
        {
            $states = State::find()
                ->where(['country_id' => $countryId])
                ->all();

            $options = '';
            foreach ($states as $state) {
                $options .= "<option value='{$state->id}'>{$state->name}</option>";
            }

            return json_encode($options);
        }

        public function actionCityList($stateId)
        {
            $cities = City::find()
                ->where(['state_id' => $stateId])
                ->all();

            $options = '';
            foreach ($cities as $city) {
                $options .= "<option value='{$city->id}'>{$city->name}</option>";
            }

            return json_encode($options);
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
        if (($model = Job::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}