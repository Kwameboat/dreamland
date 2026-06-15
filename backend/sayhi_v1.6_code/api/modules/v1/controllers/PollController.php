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
use api\modules\v1\models\TvShowEpisodeSearch;
use api\modules\v1\models\PollSearch;
use yii\web\NotFoundHttpException;

// use api\modules\v1\models\PollQuestionSearch;
/**
 * Poll Controller API
 *
 
 */
class PollController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\poll';   
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


        $model = new PollSearch();

        $result = $model->search(Yii::$app->request->queryParams);

        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        
        $response['poll']=$result;
        return $response;

        
    }

    // public function actionPollQuestion(){


    //     $model = new PollQuestionSearch();

    //     $result = $model->search(Yii::$app->request->queryParams);

    //     $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        
    //     $response['pollQuestion']=$result;
    //     return $response;

        
    // }


    public function actionCreate()
    {
        $userId = @Yii::$app->user->identity->id;
        $model = new Poll();
        $modelPollOption = new PollQuestionOption();
        $modelCategory = new Category();
        $model->scenario = 'create';

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if (!$model->validate()) {
                $response['statusCode'] = 422;
                $response['errors'] = $model->errors;
                return $response;
            }
          

            $category = $modelCategory->findOne($model->category_id);
            if (!$category) {
                $response['statusCode'] = 422;
                $response['errors'] = 'Category not found';
                return $response;
            }
           
             //  $model->start_time = strtotime(@$model->start_time);
             //  $model->end_time   = strtotime(@$model->end_time);    
               $model->created_at   = time();   
               $model->created_by   = @Yii::$app->user->identity->id;   
               $model->status   = Poll::STATUS_ACTIVE;
               $model->created_by_poll   = Poll::CREATED_BY_POLL_USER;
               
            //    $model->updated_at   = time();
            //    $model->updated_by   = @Yii::$app->user->identity->id;
            // print_r(Yii::$app->request->Post());
            // exit('99');
            if ($model->save()) {

              
                $pollOptions = Yii::$app->request->Post('options');
                $modelPollOption->insertPollOptions($model->id,$pollOptions);

                $response['message'] = "Poll has been created successfully";
                $response['id'] = $model->id;
                
                return $response;
            } else {

                $response['statusCode'] = 422;
                $errors['message'][] = 'Poll has been not created successfully';
                $response['errors'] = $errors;
                return $response;

            }

        }

    }
    public function actionView($id)
    {
        
        $model =  $this->findModel($id);
       
        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        
        $response['poll']=$model;
       
        return $response;

    }

    public function actionUpdate($id)
    {
        $userId = @Yii::$app->user->identity->id;
        $model =  $this->findModel($id);
        $modelPollOption = new PollQuestionOption();

        $model->scenario = 'update';

        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
        $model->updated_at   = time();
        $model->updated_by   = $userId;
        if($model->save()){
            $response['message']=Yii::$app->params['apiMessage']['poll']['updated'];
            $response['poll_id']=$model->id;
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
        $modelPoll = new Poll();

        $model= $modelPoll->find()->where(['id'=>$id, 'created_by_poll'=> Poll::CREATED_BY_POLL_USER,'created_by'=>$userId])->andWhere(['status'=>Poll::STATUS_ACTIVE])->one();
        if($model){
            $model->status = Poll::STATUS_DELETED;
            if($model->save(false)){
                
                $response['message']=Yii::$app->params['apiMessage']['poll']['deleted'];
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
        if (($model = Poll::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}