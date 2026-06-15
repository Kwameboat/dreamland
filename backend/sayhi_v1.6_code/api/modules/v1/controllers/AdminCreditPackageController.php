<?php

namespace api\modules\v1\controllers;

use common\models\CreditPackage;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\ActiveController;

class AdminCreditPackageController extends ActiveController
{
    public $modelClass = 'common\models\CreditPackage';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['update'], $actions['index'], $actions['delete'], $actions['view']);
        return $actions;
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),
            'authMethods' => [HttpBearerAuth::className()],
        ];
        return $behaviors;
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        $role = (int) Yii::$app->user->identity->role;
        if (!in_array($role, [1, 2], true)) {
            Yii::$app->response->statusCode = 403;
            Yii::$app->response->data = ['statusCode' => 403, 'message' => 'Admin access required.'];
            return false;
        }
        return true;
    }

    public function actionIndex()
    {
        return ['message' => 'ok', 'packages' => CreditPackage::find()->orderBy(['created_at' => SORT_DESC])->all()];
    }

    public function actionCreate()
    {
        $model = new CreditPackage();
        $model->load(Yii::$app->request->getBodyParams(), '');
        if (!$model->save()) {
            return ['statusCode' => 422, 'errors' => $model->errors];
        }
        return ['message' => 'Package created.', 'package' => $model];
    }

    public function actionUpdate($id)
    {
        $model = CreditPackage::findOne($id);
        if (!$model) {
            return ['statusCode' => 404, 'message' => 'Package not found.'];
        }
        $model->load(Yii::$app->request->getBodyParams(), '');
        if (!$model->save()) {
            return ['statusCode' => 422, 'errors' => $model->errors];
        }
        return ['message' => 'Package updated.', 'package' => $model];
    }

    public function actionDelete($id)
    {
        $model = CreditPackage::findOne($id);
        if (!$model) {
            return ['statusCode' => 404, 'message' => 'Package not found.'];
        }
        $model->is_active = 0;
        $model->save(false);
        return ['message' => 'Package deactivated.'];
    }
}
