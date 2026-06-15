<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use api\modules\v1\models\Audio;
use api\modules\v1\models\AudioSearch;

class AudioController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\audio';   
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

        $model = new AudioSearch();
        $result = $model->search(Yii::$app->request->queryParams);
        $response['message']='ok';
        $response['audio']=$result;
        return $response; 
    }

}


