<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;

use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use api\modules\v1\models\User;

use api\modules\v1\models\Payment;
use api\modules\v1\models\PodcastShow;
use api\modules\v1\models\PodcastShowSearch;
use api\modules\v1\models\PodcastShowEpisodeSearch;
use api\modules\v1\models\PodcastShowEpisode;
/**
 * live tv Controller API
 *
 
 */
class PodcastShowController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\PodcastShow';   
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


        $model = new PodcastShowSearch();

        $result = $model->search(Yii::$app->request->queryParams);

        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        
        $response['podcast_show']=$result;
        return $response;

        
    }

    public function actionPodcastShowEpisodes(){


        $model = new PodcastShowEpisodeSearch();

        $result = $model->search(Yii::$app->request->queryParams);

        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        
        $response['podcastShowEpisode']=$result;
        return $response;

        
    }

    public function actionPodcastShowDetails($id){

        $model =  new PodcastShow();     
        $result = $model->find()->where(['podcast_show.id'=>$id])->one();      
        $response['podcastShowDetails']=   $result; 
        return $response; 
        
    }

}


