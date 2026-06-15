<?php

namespace api\modules\v1\controllers;

use yii\rest\ActiveController;
use api\modules\v1\models\State;

class StateController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\state';   
    
    public function actions()
	{
		$actions = parent::actions();

		// disable default actions
		unset($actions['create'], $actions['update'], $actions['index'], $actions['delete'], $actions['view']);                    

		return $actions;
	}    


    public function actionIndex(){
        
        $params = \Yii::$app->request->queryParams;
        $countryId=       @$params['country_id'];
        $model =  new State();

        $modelResult  =$model->getStateList($countryId); 
         $response['message']='Ok';
        $response['state']=$modelResult;
        
        return $response;
    }


}


