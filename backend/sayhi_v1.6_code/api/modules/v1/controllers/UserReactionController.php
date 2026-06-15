<?php
namespace api\modules\v1\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;

use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;

use api\modules\v1\models\UserReaction;
use api\modules\v1\models\UserReactionSearch;

class UserReactionController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\userReaction';
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

    public function actionIndex(){
        $model = new UserReactionSearch();
        $result = $model->search(Yii::$app->request->queryParams);
        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        $response['reactions']=$result;
        return $response;
    }
    public function actionCreate()
    {
        $userId = Yii::$app->user->identity->id;
        $model = new UserReaction();
        $model->scenario = 'create';
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if (!$model->validate()) {
                $response['statusCode'] = 422;
                $response['errors'] = $model->errors;
                return $response;
            }

            $referenceId    = @(int) $model->reference_id;
            $type           = @(int) $model->type;
            $reactionResult =   $model->find()->where(['user_id'=>$userId,'type'=>$type,'reference_id'=>$referenceId])->one();
           

            if($reactionResult){
                $reactionResult->reaction = $model->reaction;
            }else{
                $reactionResult = $model;
            }
            if ($reactionResult->save(false)) {
                $response['message'] = Yii::$app->params['apiMessage']['common']['actionSuccess'];
                $response['id'] = $reactionResult->id;
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