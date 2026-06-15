<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use api\modules\v1\models\Business;
use api\modules\v1\models\BusinessSearch;
/**
 * Business Controller API
 *
 
 */
class BusinessController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\Business';   
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


        $model = new BusinessSearch();

        $result = $model->search(Yii::$app->request->queryParams);

        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        
        $response['business']=$result;
        return $response;

        
    }

    public function actionLists(){

        $model =  new Business();     
        $result = $model->find()->where(['business.status'=>Business::STATUS_ACTIVE])->orderBy(['business.name'=>SORT_DESC])->all();        
        $response['businessLists']=$result;
        return $response;

        
    }

    public function actionMyFavoriteList()
    {

        $model = new BusinessSearch();

        $result = $model->BusinessMyFavorite(Yii::$app->request->queryParams);

        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        
        $response['couponFavoriteList']=$result;
        return $response;

        
    }

}


