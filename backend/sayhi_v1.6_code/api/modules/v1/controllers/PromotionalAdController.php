<?php

namespace api\modules\v1\controllers;
use yii\rest\ActiveController;
use api\modules\v1\models\PromotionalAd;
use api\modules\v1\models\PromotionalAdSearch;
use Yii;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;


/**
 * 
 *
 
 */
class PromotionalAdController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\PromotionalAd';   
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


    public function actionIndex(){
        $model =  new PromotionalAdSearch();
        
        
        $result = $model->search(Yii::$app->request->queryParams);
        
        $response['message']='ok';
        $response['ad']=$result;
        return $response;
    }


}


