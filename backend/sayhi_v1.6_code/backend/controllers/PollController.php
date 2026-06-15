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
use common\models\PollQuestionOption;
use common\models\Post;
use yii\filters\AccessControl;

/**
 * 
 */
class PollController extends Controller
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
                        'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::POLL),
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
        $searchModel = new PollSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $modelCategory = new Category();
        $resultCategory = $modelCategory->find()->select(['id','name'])->where(['type'=>Category::TYPE_POLL])->andWhere(['and', 'status', Category::STATUS_ACTIVE])->all(); 
       
        $categoryData = ArrayHelper::map($resultCategory,'id','name');

        $modelOrganization = new Organization(); 
        $resultOrganization = $modelOrganization->find()->select(['id','name'])->andWhere(['<>', 'status', Organization::STATUS_DELETED])->all(); 
        $organizationData = ArrayHelper::map($resultOrganization,'id','name');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'categoryData' =>$categoryData,
            'organizationData'=>$organizationData
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
        
        $model = new Poll();
        $modelPollQuestionOption = new PollQuestionOption();
        $model->scenario = 'create';
        $modelCategory = new Category(); 
        $resultCategory = $modelCategory->find()->select(['id','name'])->where(['type'=>Category::TYPE_POLL])->andWhere(['and', 'status', Category::STATUS_ACTIVE])->all(); 
        $categoryData = ArrayHelper::map($resultCategory,'id','name');

        $modelOrganization = new Organization(); 
        $resultOrganization = $modelOrganization->find()->select(['id','name'])->andWhere(['<>', 'status', Organization::STATUS_DELETED])->all(); 
        $organizationData = ArrayHelper::map($resultOrganization,'id','name');
        if ($model->load(Yii::$app->request->post()) ) {
            $modelUser = new User();
            $modelUser->checkPageAccess();
            
            $model->created_at     = strtotime("now");
            $model->updated_at     = strtotime("now");
            $model->created_by     =  Yii::$app->user->identity->id;
            $model->updated_by     =  Yii::$app->user->identity->id;
            $model->type           =  Poll::TYPE_POLL;
            $pollOptions = Yii::$app->request->post("pollOption");
            if($model->validate() && count($pollOptions) >= 3){
              $model->start_time     = strtotime($model->start_time);
              $model->end_time       = strtotime($model->end_time.' 23:59:59');
                if($model->save()){ 
                    // $pollOptions = Yii::$app->request->post("pollOption");
                    $inputPollOptions['pollId']          =  $model->id;
                    $inputPollOptions['poll_options']    =  $pollOptions;
  
                    $modelPollQuestionOption->insertPollOptions($inputPollOptions);

                    $modelPost = new Post();
                    $modelPost->type =  Post::TYPE_NORMAL;
                    $modelPost->post_content_type =  Post::CONTENT_TYPE_POLL;
                    $modelPost->content_type_reference_id =  $model->id;
                    $modelPost->is_add_to_post =  1;
                    $modelPost->save();


                    Yii::$app->session->setFlash('success', "Poll created successfully");
                    return $this->redirect(['index']);
                }
            }else{
                if(count($pollOptions) < 3){
                    Yii::$app->session->setFlash('error', "Please create minimum two options!");
                }
                
                // $model->addError('pollOptions', 'Please Post minimum two options!');
            }
            
        }

        return $this->render('create', [
            'model' => $model,
            'categoryData'=>$categoryData,
            'organizationData'=>$organizationData,
            'modelPollQuesOption' => $modelPollQuestionOption
            
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
        $modelPollQuestionOption = new PollQuestionOption();
        $resultCategory = $modelCategory->find()->select(['id','name'])->where(['type'=>Category::TYPE_POLL])->andWhere(['and', 'status', Category::STATUS_ACTIVE])->all(); 
        $categoryData = ArrayHelper::map($resultCategory,'id','name');

        $modelOrganization = new Organization(); 
        $resultOrganization = $modelOrganization->find()->select(['id','name'])->andWhere(['<>', 'status', Organization::STATUS_DELETED])->all(); 
        $organizationData = ArrayHelper::map($resultOrganization,'id','name');


        $model = $this->findModel($id);

        $model->scenario = 'update';

        //if($model->load(Yii::$app->request->post()) && $model->validate()) {
        if($model->load(Yii::$app->request->post())){
            $modelUser = new User();
            $modelUser->checkPageAccess();
          
            $model->start_time     = strtotime($model->start_time);
            $model->end_time       = strtotime($model->end_time.' 23:59:59');
            $model->updated_at     = strtotime("now");
            $model->updated_by     =  Yii::$app->user->identity->id;   
            $model->type           =  Poll::TYPE_POLL;   
            $model->created_by_poll  =  Poll::CREATED_BY_POLL_ADMIN;    
            if($model->save(false)){
                Yii::$app->session->setFlash('success', "Poll updated successfully");
                return $this->redirect(['index']);
            }
                
        }else{
            $model->start_time              = date('Y-m-d',$model->start_time);
            $model->end_time              = date('Y-m-d',$model->end_time);
        } 
       
        return $this->render('update', [
            'model' => $model,
            'categoryData'=>$categoryData,
            'organizationData'=>$organizationData,
            'modelPollQuesOption' => $modelPollQuestionOption
       
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
        $modelUser = new User();
        $modelUser->checkPageAccess();

        $userModel= $this->findModel($id);
        $userModel->status =  Poll::STATUS_DELETED;
        if($userModel->save(false)){

            Yii::$app->session->setFlash('success', "Poll deleted successfully");

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
        if (($model = Poll::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}