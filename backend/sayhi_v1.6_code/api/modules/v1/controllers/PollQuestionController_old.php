<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;

use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use api\modules\v1\models\User;
use api\modules\v1\models\LiveTv;
use api\modules\v1\models\LiveTvSearch;
use api\modules\v1\models\LiveTvSubscriber;
use api\modules\v1\models\LiveTvViewer;
use api\modules\v1\models\LiveTvFavorite;
use api\modules\v1\models\TvShowSearch;

use api\modules\v1\models\Payment;
use api\modules\v1\models\TvShowEpisodeSearch;
use api\modules\v1\models\PollSearch;
use api\modules\v1\models\PollQuestionSearch;
/**
 * live tv Controller API
 *
 
 */
class PollQuestionController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\pollQuestion';   
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


        $model = new PollQuestionSearch();
        
        $result = $model->search(Yii::$app->request->queryParams);
        // print_r($result);
        // exit("fl");
        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        
        $response['pollQuestion']=$result;
        return $response;

        
    }

    // public function actionPollQuestion(){


    //     $model = new PollQuestionSearch();

    //     $result = $model->search(Yii::$app->request->queryParams);

    //     $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        
    //     $response['pollQuestion']=$result;
    //     return $response;

        
    // }


}


