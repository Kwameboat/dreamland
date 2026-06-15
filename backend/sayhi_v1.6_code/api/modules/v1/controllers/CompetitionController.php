<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;

use api\modules\v1\models\User;
use api\modules\v1\models\Competition;
use api\modules\v1\models\CompetitionUser;
use api\modules\v1\models\CompetitionSearch;


class CompetitionController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\Competition';   
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

        $model = new CompetitionSearch();
        $result = $model->search(Yii::$app->request->queryParams);
        $response['message']=Yii::$app->params['apiMessage']['common']['recordFound'];
        $response['competition']=$result;
        return $response; 
    }

    
    /**
     * detail 
     */
    public function actionView($id){
        $model =  new Competition();
        
        $result = $model->find()->where(['competition.id'=>$id])
        ->joinWith(['post.user' => function($query){
            $query->select(['id','name','username','email','bio','description','image','country_code','phone','country','city','sex','is_chat_user_online','chat_last_time_online','location','latitude','longitude']);
        }])
        ->joinWith(['competitionPosition.post' => function($query){
            $query->select(['id','type','competition_id','user_id','title','image','total_view','total_like','total_comment','total_share','popular_point','status','created_at']);
             
        }])
        ->joinWith(['competitionPosition.post.user' => function($query){
            $query->select(['id','name','username','email','bio','description','image','country_code','phone','country','city','sex','is_chat_user_online','chat_last_time_online','location','latitude','longitude']);
             
        }])
        
        ->one();
        
        $response['competition']=   $result; 
        return $response; 

        
    }



    public function actionMyCompetition(){

        $model = new CompetitionSearch();
        $result = $model->searchMyCompetition(Yii::$app->request->queryParams);
        $response['message']=Yii::$app->params['apiMessage']['common']['recordFound'];
        $response['competition']=$result;
        return $response; 
    }



    public function actionJoin()
    {
        $userId                 = Yii::$app->user->identity->id;
     
        $model                    =   new Competition();
        $modelCompetitionUser   =   new CompetitionUser();
        $modelUser   =   new User();
        $resultUser = $modelUser->findOne($userId);
       
        $model->scenario ='join';
        
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }
            $currentTime=time();
           $competitionId =  @(int) $model->competition_id;
           $resultCompetition     = $model->findOne($competitionId);
           
            if(!$resultCompetition){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['competition']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
            }
    
            if($resultCompetition->start_date > $currentTime || $resultCompetition->end_date < $currentTime ){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['competition']['notAvailable'];
                $response['errors']=$errors;
                return $response;
            
            }
            $resultCountCompetitionUser =$modelCompetitionUser->find()->where(['user_id'=>$userId,'competition_id'=>$competitionId])->count();

            if($resultCountCompetitionUser>0){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['competition']['alreadyJoinedCompetition'];
                $response['errors']=$errors;
                return $response;
            
            }


            if($resultCompetition->joining_fee > $resultUser->available_coin){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['competition']['joiningFeeNotAvailable'];
                $response['errors']=$errors;
                return $response;
            
            }

           
            //resultUser
            $modelCompetitionUser->user_id          =   $userId;
            $modelCompetitionUser->competition_id   =   $competitionId;

            if($modelCompetitionUser->save()){
                $resultUser->available_coin  =  $resultUser->available_coin-$resultCompetition->joining_fee;
                if($resultUser->save(false)){

                }
                
                $response['message']=Yii::$app->params['apiMessage']['competition']['joinSuccess'];
                return $response; 
            }else{

                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;
            
            

            }

            
        }

       
        
    }


    protected function findModel($id)
    {
        if (($model = Competition::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }



}


