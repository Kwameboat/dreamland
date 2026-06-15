<?php
namespace api\modules\v1\controllers;

use api\modules\v1\models\Category;
use Yii;
use yii\rest\ActiveController;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;

use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use api\modules\v1\models\User;
use api\modules\v1\models\Job;
use api\modules\v1\models\JobApplications;
use api\modules\v1\models\JobApplicationSearch;
use yii\web\NotFoundHttpException;

// use api\modules\v1\models\PollQuestionSearch;
/**
 * Poll Option Controller API
 *
 
 */
class JobApplicationController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\JobApplicatios';   
    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];
    
    public function actions()
	{
		$actions = parent::actions();

		// disable default actions
		unset($actions['create'], $actions['update'], $actions['index'], $actions['delete'], $actions['view']);                    

		return $actions;
	}    
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),
            'except'=>[],
            'authMethods' => [
                HttpBearerAuth::className()
            ],
        ];
        return $behaviors;
    }

    public function actionIndex(){


        $model = new JobApplicationSearch();

        $result = $model->search(Yii::$app->request->queryParams);

        $response['message'] = Yii::$app->params['apiMessage']['jobApplication']['listFound'];
        
        $response['jobApplications']=$result;
        return $response;

        
    }

    public function actionCreate()
    {
        $userId = @Yii::$app->user->identity->id;
        $modelJob = new Job();
        $model = new JobApplications();
        $model->scenario = 'create';

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if (!$model->validate()) {
                $response['statusCode'] = 422;
                $response['errors'] = $model->errors;
                return $response;
            }
          

            $jobApply = $modelJob->findOne($model->job_id);
            if (!$jobApply) {
                $response['statusCode'] = 422;
                $errors['message'][] ='Job id not found.';
                $response['errors']=$errors;
                return $response;
            }
            $jobApplyData =   JobApplications::find()->where(['job_id'=>$model->job_id, 'user_id'=>@Yii::$app->user->identity->id])->one();
            if($jobApplyData){
                $response['statusCode']=422;
                $errors['message'][] =Yii::$app->params['apiMessage']['jobApplication']['alreadyApply'];
                $response['errors']=$errors;
                return $response;
            }

            $model->status   = JobApplications::STATUS_PENDING;
            if ($model->save()) {
                
                $response['message'] = Yii::$app->params['apiMessage']['jobApplication']['created'];
                $response['id'] = $model->id;
                
                return $response;
            } else {

                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;

            }

        }

    }

    public function actionUpdate($id)
    {
        
        $userId = @Yii::$app->user->identity->id;
        $model =  $this->findModel($id);
        $modelJob = new Job();
        $model->scenario = 'update';

        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
        $jobApply = $modelJob->findOne($model->job_id);
            if (!$jobApply) {
                $response['statusCode'] = 422;
                $errors['message'][] ="Job id not found.";
                $response['errors']=$errors;
                return $response;
            }
        $jobApplyData =   JobApplications::find()->where(['id'=>$model->id, 'user_id'=>@Yii::$app->user->identity->id])->one();
            if(!$jobApplyData){
                $response['statusCode']=422;
                $errors['message'][] ="You are not allow to take this action.";
                $response['errors']=$errors;
                return $response;
            }
        if($model->save()){
            $response['message']=Yii::$app->params['apiMessage']['jobApplication']['updated'];
            $response['job_application_id']=$model->id;
            return $response; 
        }else{
            $response['statusCode']=422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors']=$errors;
            return $response;
        }

    }

    public function actionDelete($id){
        
        $userId = Yii::$app->user->identity->id;
        $modelOption = new JobApplications();

        $model= $modelOption->find()->where(['id'=>$id, 'status'=>JobApplications::STATUS_PENDING , 'user_id'=>$userId])->one();
        if($model){
            $model->status = JobApplications::STATUS_DELETED;
            if($model->save(false)){
                
                $response['message']=Yii::$app->params['apiMessage']['jobApplication']['deleted'];
                return $response; 
            }

            
        }else{

            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
            return $response;
        }
        
    }


    protected function findModel($id)
    {
        if (($model = JobApplications::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}