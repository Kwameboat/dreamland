<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;

use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use api\modules\v1\models\User;
use api\modules\v1\models\ProfileCategoryType;
/**
 * live tv Controller API
 *
 
 */
class ProfileCategoryTypeController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\ProfileCategoryType';   
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

        $model =  new ProfileCategoryType();     
        $result = $model->find()->where(['profile_category_type.status' => ProfileCategoryType::STATUS_ACTIVE])->all();        
        $response['profileCategoryType']=$result;
        return $response;
        
    }


}


