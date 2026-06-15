<?php

namespace api\modules\v1\controllers;
use yii\rest\ActiveController;
use yii;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use api\modules\v1\models\Organization;
use api\modules\v1\models\OrganizationSearch;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;

class OrganizationController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\campaign';   
    
    public function actions()
	{
		$actions = parent::actions();
		unset($actions['create'], $actions['update'], $actions['index'], $actions['delete']);                    

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


    // public function actionIndex(){
    //     $model =  new OrganizationSearch();
    //     $modelRes= $model->find()->all();
        
       
    //    $response['message']='ok';
    //     $response['Orgnization']=$modelRes;
    //     return $response;
    // }


    public function actionIndex(){


        $model = new OrganizationSearch();

        $result = $model->search(Yii::$app->request->queryParams);

        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        
        $response['orgnization']=$result;
        return $response;

        
    }
   

    public function actionList(){
        $model =  new OrganizationSearch();
        $modelRes= $model->find()->where(['status'=>Organization::STATUS_ACTIVE])->orderBy(['id'=>SORT_DESC])->all();
        $response['message']='ok';
        $response['orgnizationList']=$modelRes;
        return $response;
    }



}


