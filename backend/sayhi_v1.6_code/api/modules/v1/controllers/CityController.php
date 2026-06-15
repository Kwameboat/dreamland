<?php
namespace api\modules\v1\controllers;
use yii\rest\ActiveController;
use api\modules\v1\models\City;

class CityController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\city';   
    
    public function actions()
	{
		$actions = parent::actions();

		// disable default actions
		unset($actions['create'], $actions['update'], $actions['index'], $actions['delete'], $actions['view']);                    

		return $actions;
	}    


    public function actionIndex(){
        
        $params = \Yii::$app->request->queryParams;
        $stateId=       @$params['state_id'];
        $model =  new City();

        $modelResult  =$model->getCityList($stateId); 
         $response['message']='Ok';
        $response['city']=$modelResult;
        
        return $response;
    }


}


