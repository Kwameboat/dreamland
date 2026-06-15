<?php

namespace api\modules\v1\controllers;
use yii\rest\ActiveController;
use api\modules\v1\models\Setting;

use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;


/**
 * setting Controller API
 *
 
 */
class SettingController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\setting';   
    
    public function actions()
	{
		$actions = parent::actions();

		// disable default actions
		unset($actions['create'], $actions['update'], $actions['index'], $actions['delete'], $actions['view']);                    

		return $actions;
	}    


    public function actionIndex(){
        $model =  new Setting();
        $modelRes= $model->find()->one();
        
       
       $response['message']='ok';
        $response['setting']=$modelRes;
        return $response;
    }

    


}


