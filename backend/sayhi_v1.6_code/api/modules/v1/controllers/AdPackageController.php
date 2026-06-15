<?php

namespace api\modules\v1\controllers;
use yii\rest\ActiveController;
use api\modules\v1\models\AdPackage;
use api\modules\v1\models\PromotionalBanner;

use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;



/**
 * package Controller API
 *
 
 */
class AdPackageController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\adPackage';   
    
    public function actions()
	{
		$actions = parent::actions();

		// disable default actions
		unset($actions['create'], $actions['update'], $actions['index'], $actions['delete'], $actions['view']);                    

		return $actions;
	}    


    public function actionIndex(){
        $model =  new AdPackage();
        $modelPromotionalBanner =  new PromotionalBanner();
        
        $bannerPackage = $modelPromotionalBanner->getAllPromotionalBanner();
        $ordinaryPackage = $model->getOrdinaryPackage();
        
       
       $response['message']='ok';
        $response['bannerPackage']=$bannerPackage;
        $response['ordinaryPackage']=$ordinaryPackage;
        
        return $response;
    }


}


