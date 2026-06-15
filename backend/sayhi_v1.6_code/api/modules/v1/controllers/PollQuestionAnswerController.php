<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;

use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use api\modules\v1\models\User;
use api\modules\v1\models\LiveTv;
use api\modules\v1\models\LiveTvSearch;
use api\modules\v1\models\LiveTvSubscriber;
use api\modules\v1\models\LiveTvViewer;
use api\modules\v1\models\LiveTvFavorite;
use api\modules\v1\models\TvShowSearch;

use api\modules\v1\models\Payment;
use api\modules\v1\models\Poll;
use api\modules\v1\models\TvShowEpisodeSearch;
use api\modules\v1\models\PollSearch;
use api\modules\v1\models\PollQuestionSearch;
use api\modules\v1\models\PollQuestionAnswer;
use api\modules\v1\models\PollQuestionAnswerSearch;
use api\modules\v1\models\PollQuestion;
use api\modules\v1\models\PollQuestionOption;
/**
 * live tv Controller API
 *
 
 */
class PollQuestionAnswerController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\pollQuestionAnswer';   
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


        $model = new PollQuestionAnswerSearch();
        
        $result = $model->search(Yii::$app->request->queryParams);
        // print_r($result);
        // exit("fl");
        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        
        $response['pollQuestionAnswer']=$result;
        return $response;

        
    }

    public function actionAddAnswer()
    {

        $userId = Yii::$app->user->identity->id;
        $model =   new PollQuestionAnswer();
        $model->scenario ='create';
        if (Yii::$app->request->isPost) {
            $model->user_id = $userId;
            $model->created_at = strtotime('now');
            
            $model->status = 10;
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }   
            // $poll_question_id = $model->poll_question_id;
            $poll_id = $model->poll_id;
            $totalCount = $model->find()->where(['user_id'=>$userId, 'poll_id'=>$poll_id])->count();
           
            if($totalCount>0){
            $response['statusCode']=422;
            $errors['message'][] =   Yii::$app->params['apiMessage']['pollQuestion']['alreadyAddedInList'];
            $response['errors']=$errors;
            return $response; 
            }
            if($model->save()){
                $response['message']=Yii::$app->params['apiMessage']['pollQuestion']['addedAnswer'];
                $result = Poll::find()->andWhere(['poll.id'=>$poll_id])->andwhere(['poll.status'=>Poll::STATUS_ACTIVE])->all();
                
                $pollQuestionOption = PollQuestionOption::find()->andWhere(['poll_qustion_options.poll_id'=>$poll_id])->andwhere(['poll_qustion_options.status'=>PollQuestionOption::STATUS_ACTIVE])->all();
                $response['result']['question'] = $result;
                $response['result']['questionOption']= $pollQuestionOption;
                return $response; 
            }else{
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;
            }
        }

    }


}


