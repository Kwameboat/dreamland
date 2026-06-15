<?php
namespace api\modules\v1\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\imagine\Image;
use yii\db\Expression;
use yii\rest\ActiveController;
use yii\web\UploadedFile;
use api\modules\v1\models\ReportedContent;
//use api\modules\v1\models\PostComment;
use api\modules\v1\models\User;



class ReportedContentController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\reportedContent';
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
                HttpBearerAuth::className(),
            ],
        ];
        return $behaviors;
    }


    public function actionCreate()
    {

        $model = new ReportedContent();
        $userId = Yii::$app->user->identity->id;

        $model->scenario = 'create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;

            return $response;
        }

        $referenceId = @(int) $model->reference_id;

        $totalCount = $model->find()->where(['reference_id' => $referenceId,'type'=>$model->type, 'user_id' => $userId, 'status' => ReportedContent::STATUS_PENDING])->count();
        if ($totalCount > 0) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['reportedContent']['alreadyReported'];
            $response['errors'] = $errors;
            return $response;

        }

        $model->status = ReportedContent::STATUS_PENDING;
        if ($model->save(false)) {
            $response['message'] = Yii::$app->params['apiMessage']['reportedContent']['reportedSuccess'];
            return $response;
        } else {

            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
        }
    }

 


    protected function findModel($id)
    {
        if (($model = ReportedContent::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}