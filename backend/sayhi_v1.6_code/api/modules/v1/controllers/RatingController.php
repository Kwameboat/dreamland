<?php
namespace api\modules\v1\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;

use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;

use api\modules\v1\models\Rating;
use api\modules\v1\models\RatingSearch;



/**
 * Controller API
 *
 
 */
class RatingController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\rating';
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
            'except' => [],
            'authMethods' => [
                HttpBearerAuth::className()
            ],
        ];
        return $behaviors;
    }


    public function actionIndex()
    {


        $model = new RatingSearch();

        $result = $model->search(Yii::$app->request->queryParams);

        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];

        $response['rating'] = $result;
        return $response;


    }



    public function actionCreate()
    {
        $userId = Yii::$app->user->identity->id;
        $model = new Rating();
        $model->scenario = 'create';
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if (!$model->validate()) {
                $response['statusCode'] = 422;
                $response['errors'] = $model->errors;
                return $response;
            }
            $type = @(int) $model->type;
            $referenceId = @(int) $model->reference_id;
            $resultCount = $model->find()->where(['type' => $type, 'reference_id' => $referenceId, 'user_id' => $userId])->count();
            if ($resultCount > 0) {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['rating']['alreadyGiven'];
                $response['errors'] = $errors;
                return $response;
            }

            if ($model->save()) {

                $response['message'] = Yii::$app->params['apiMessage']['rating']['createdSuccess'];
                return $response;

            } else {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors'] = $errors;
                return $response;
            }
        }

    }
}