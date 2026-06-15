<?php

namespace api\modules\v1\controllers;

use yii\rest\ActiveController;
use api\modules\v1\models\Country;
use api\modules\v1\models\CountrySearch;

class CountryController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\country';   
    
    public function actions()
	{
		$actions = parent::actions();

		// disable default actions
		unset($actions['create'], $actions['update'], $actions['index'], $actions['delete'], $actions['view']);                    

		return $actions;
	}    


    public function actionIndex(){
        
        $model =  new Country();
        $modelResult  =$model->find()->where(['status'=>Country::STATUS_ACTIVE])->orderBy(['name'=>SORT_ASC])->all(); 
         $response['message']='Ok';
        $response['country']=$modelResult;
        
        return $response;
    }
    
    public function actionSearchLocation(){
        $params = @\Yii::$app->request->queryParams;
    //    echo $title=       @$params['name'];
        // $model =  new Country();
        $modelSearch =  new CountrySearch();
        $modelResult  = $modelSearch->search($params);
        $countryResult  =Country::find()->select(['id','name', 'name as fullname' ])->where(['status'=>Country::STATUS_ACTIVE])->andWhere(['=', 'country.name', @$params['name']])->orderBy(['name'=>SORT_ASC])->one(); 
        $models = $modelResult->getModels();
        $data = [];
        if($countryResult){
            $data[$countryResult->id]['id'] = $countryResult->id;
            // $data[$countryResult->id]['name'] = $countryResult->name;
            $data[$countryResult->id]['fullname'] = $countryResult->fullname;
            $data[$countryResult->id]['type'] = 'country';
        }

        foreach ($models as $model) {
            $data[$model->id]['id'] = $model->id;
            // $data[$model->id]['name'] = $model->name;
            $data[$model->id]['fullname'] = $model->fullname;
            $data[$model->id]['type'] = 'city';
        }
        $dataResult = [];
        if($data){
           $dataResult = array_values($data);
        }
       
        $response = [];
        $response['message'] = 'Ok';
        $response['regional_location'] = $dataResult;

        return $response;
    }

}


