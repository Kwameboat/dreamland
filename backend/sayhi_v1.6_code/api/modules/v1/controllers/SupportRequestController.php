<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use api\modules\v1\models\SupportRequest;
use api\modules\v1\models\SupportRequestSearch;



class SupportRequestController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\SupportRequest';   
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

        $model = new SupportRequestSearch();
        $result = $model->search(Yii::$app->request->queryParams);
        $response['message']='ok';
        $response['supportRequest']=$result;
        return $response; 
    }


    
    public function actionCreate()
    {
        $userId = Yii::$app->user->identity->id;
        $model = new SupportRequest();

        $model->scenario = 'create';

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            
            if (!$model->validate()) {
                $response['statusCode'] = 422;
                $response['errors'] = $model->errors;
                return $response;
            }

            $model->user_id = $userId;

            if ($model->save()) {


                $response['message'] = Yii::$app->params['apiMessage']['supportRequest']['created'];
                $response['post_id'] = $model->id;
                
                return $response;
            } else {

                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors'] = $errors;
                return $response;

            }

        }

    }
    public function actionView($id){
        $userId = Yii::$app->user->identity->id;
        $model = new SupportRequest();

        $result = $model->findOne($id);
        $response['supportRequest']=   $result; 
        return $response; 
        

        
    }

}


