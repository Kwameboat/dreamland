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
use api\modules\v1\models\Poll;
use api\modules\v1\models\PollQuestionOption;
use api\modules\v1\models\PollSearch;
use yii\web\NotFoundHttpException;

// use api\modules\v1\models\PollQuestionSearch;
/**
 * Poll Option Controller API
 *
 
 */
class PollOptionController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\pollQuestionOption';   
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

    public function actionCreate()
    {
        $userId = @Yii::$app->user->identity->id;
        $modelPoll = new Poll();
        $model = new PollQuestionOption();
        $model->scenario = 'create';

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if (!$model->validate()) {
                $response['statusCode'] = 422;
                $response['errors'] = $model->errors;
                return $response;
            }
          

            $poll = $modelPoll->findOne($model->poll_id);
            if (!$poll) {
                $response['statusCode'] = 422;
                $response['errors'] = 'Poll id not found.';
                return $response;
            }
            $pollData =   Poll::find()->where(['id'=>$model->poll_id, 'type'=> Poll::TYPE_POST])->andWhere(['created_by_poll'=>Poll::CREATED_BY_POLL_USER,'created_by'=>$userId])->one();
            if(!$pollData){
                $response['statusCode']=422;
                $errors['message'][] = "You are not allow to take this action.";
                $response['errors']=$errors;
                return $response;
            }
            $model->status   = PollQuestionOption::STATUS_ACTIVE;
            if ($model->save()) {

                $response['message'] = Yii::$app->params['apiMessage']['poll']['createOptions'];
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
        // $modelPollOption = new PollQuestionOption();

        $model->scenario = 'update';

        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
        if($model->save()){
            $response['message']=Yii::$app->params['apiMessage']['poll']['updateOptions'];
            $response['option_id']=$model->id;
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
        $modelOption = new PollQuestionOption();

        $model= $modelOption->find()->where(['id'=>$id, 'status'=>PollQuestionOption::STATUS_ACTIVE])->one();
        if($model){
            $model->status = PollQuestionOption::STATUS_DELETED;
            if($model->save(false)){
                
                $response['message']=Yii::$app->params['apiMessage']['poll']['deleteOptions'];
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
        if (($model = PollQuestionOption::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}