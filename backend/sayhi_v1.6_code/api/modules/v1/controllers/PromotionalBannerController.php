<?php

namespace api\modules\v1\controllers;
use yii\rest\ActiveController;
use api\modules\v1\models\PromotionalAd;
use api\modules\v1\models\PromotionalAdSearch;
use api\modules\v1\models\PromotionalBanner;
use Yii;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;


/**
 * 
 *
 
 */
class PromotionalBannerController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\PromotionalBanner';   
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
        $model =  new PromotionalBanner();
        
        
        // $result = $model->search(Yii::$app->request->queryParams);
        $result = $model->find()->where(['status'=>PromotionalBanner::STATUS_ACTIVE]);
        $dataProvider = new ActiveDataProvider([
            'query' => $result,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

        return $dataProvider;
    }


}


