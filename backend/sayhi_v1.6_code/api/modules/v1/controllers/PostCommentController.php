<?php
namespace api\modules\v1\controllers;

use api\modules\v1\models\Post;
use api\modules\v1\models\PostComment;
use api\modules\v1\models\ReportedPostComment;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\ActiveController;
use yii\web\NotFoundHttpException;

class PostCommentController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\postComment';
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
            'except' => ['ad-search'],
            'authMethods' => [
                HttpBearerAuth::className(),
            ],
        ];
        return $behaviors;
    }



    public function actionDelete($id)
    {
        $userId = Yii::$app->user->identity->id;

        $model = PostComment::find()->where(['id' => $id, 'user_id' => $userId])->andWhere(['status'=>PostComment::STATUS_ACTIVE])->one();
       
        if ($model) {
            $commentStatus = PostComment::deleteAll(['id'=>$id, 'user_id' => $userId]);
            if ($commentStatus) {
                $postData = Post::find()->where(['id' => @$model->post_id])->one();
                if($postData){
                    $oldTotalcomment = @$postData->total_comment;
                    $newTotalcomment = $oldTotalcomment-1;
                    $postData->total_comment = $newTotalcomment;
                    $postData->save();
                }
                

                $response['message'] = Yii::$app->params['apiMessage']['postComment']['deleted'];

                return $response;
            } else {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors'] = $errors;
                return $response;
            }
        }else{
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
            return $response;
        }

    }

        /**
     * Report Post
     */
    public function actionReportComment()
    {

        $model = new ReportedPostComment();
        $userId = @Yii::$app->user->identity->id;

        $model->scenario = 'create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;

            return $response;
        }

        $postCommentId = @(int) $model->post_comment_id;

        $totalCount = $model->find()->where(['post_comment_id' => $postCommentId, 'user_id' => $userId, 'status' => ReportedPostComment::STATUS_PENDING])->count();
        if ($totalCount > 0) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['postComment']['alreadyReported'];
            $response['errors'] = $errors;
            return $response;

        }

        $model->status = ReportedPostComment::STATUS_PENDING;
        if ($model->save(false)) {
            $response['message'] = Yii::$app->params['apiMessage']['postComment']['reportedSuccess'];
            return $response;
        } else {

            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
        }
    }


    protected function findModel($id)
    {
        if (($model = PostComment::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}