<?php
namespace api\modules\v1\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use api\modules\v1\models\Pin;
use api\modules\v1\models\User;



class PinController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\pin';

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


    public function actionCreate()
    {

        $userId = Yii::$app->user->identity->id;
        $model = new Pin();
        $model->scenario = 'create';
        if (Yii::$app->request->isPost) {

            $model->load(Yii::$app->getRequest()->getBodyParams(), '');

            if (!$model->validate()) {

                $response['statusCode'] = 422;
                $response['errors'] = $model->errors;
                return $response;
            }

            $referenceId = @(int) $model->reference_id;
            $type = @(int) $model->type;
            $totalCont = $model->find()->where(['type'=>$type,'reference_id'=>$referenceId])->count();
            if($totalCont>0){
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['pin']['alreadyPinned'];
                $response['errors'] = $errors;
                return $response;

            }
            
            if ($type == Pin::TYPE_POST ) {
                $totalPin = $model->find()->where(['user_id'=>$userId, 'type'=>$type])->count();
                if($totalPin>2){
                    $lastTwoRecords = Pin::find()
                    ->where(['user_id'=>$userId, 'type'=>$type])
                    ->orderBy(['id' => SORT_DESC]) 
                    ->limit(2)
                    ->all();
                    $idsToKeep = array_column($lastTwoRecords, 'id');
                    Pin::deleteAll(['AND', ['user_id'=>$userId, 'type'=>$type], ['NOT IN', 'id', $idsToKeep]]);

                }

            } 


            if ($model->save()) {

                $response['message'] = Yii::$app->params['apiMessage']['pin']['pinned'];
                $response['id']=$model->id;
                return $response;
            } else {

                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors'] = $errors;
                return $response;

            }


        }

    }
   
    public function actionDelete($id)
    {
        $userId = Yii::$app->user->identity->id;
        $model                  = new Pin();
        $pinResult = $model->find()->where(['user_id'=>$userId, 'id'=>$id])->one();
        if($pinResult){
            if($pinResult->delete()) 
            {
                $response['message'] = Yii::$app->params['apiMessage']['pin']['removed'];
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