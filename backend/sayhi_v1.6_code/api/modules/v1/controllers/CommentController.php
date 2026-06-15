<?php
namespace api\modules\v1\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\db\Expression;
use yii\rest\ActiveController;

use api\modules\v1\models\PostComment;
use api\modules\v1\models\CampaignComment;
use api\modules\v1\models\Comment;
use api\modules\v1\models\CommentLike;
use api\modules\v1\models\Notification;


class CommentController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\comment';
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

    /**
     * like comment
     */

    public function actionLike()
    {
        $model = new CommentLike();
        $userId = Yii::$app->user->identity->id;
        $model->scenario = 'create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        $commentId = @(int) $model->comment_id;
        $sourceType = @(int) $model->source_type;

        $totalCount = $model->find()->where(['comment_id' => $commentId,'source_type'=>$sourceType, 'user_id' => $userId])->count();

        if ($totalCount > 0) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['comment']['alreadyLiked'];
            $response['errors'] = $errors;
            return $response;

        }

        if ($model->save(false)) {
            
            $commentlikeTemplate = '';
            $toUserId = '';
            
            if($sourceType== CommentLike::SOURCE_TYPE_POST  ){
                $commentlikeTemplate = 'commentlikePost';
                $modelPostComment   = new PostComment();
                $commentResult =  $modelPostComment->findOne($commentId);
                $toUserId   = $commentResult->user_id;
                

            }else if($sourceType== CommentLike::SOURCE_TYPE_CAMPAIGN  ){
                $commentlikeTemplate = 'commentlikeCampaign';
                $modelCampaignComment   = new CampaignComment();
                $commentResult =  $modelCampaignComment->findOne($commentId);
                $toUserId   = $commentResult->user_id;
            }else if($sourceType== CommentLike::SOURCE_TYPE_COUPON  ){
                $commentlikeTemplate = 'commentlikeCoupon';
                $modelComment   = new Comment();
                $commentResult =  $modelComment->findOne($commentId);
                $toUserId   = $commentResult->user_id;
            }


            $modelNotification = new Notification();
            $notificationInput = [];
            $notificationData = Yii::$app->params['pushNotificationMessage'][$commentlikeTemplate];
            $replaceContent = [];
            $replaceContent['USER'] = Yii::$app->user->identity->username;
            $notificationData['body'] = $modelNotification->replaceContent($notificationData['body'], $replaceContent);

            $userIds = [];
            $userIds[] = $toUserId;

            $notificationInput['referenceId'] = $commentId;
            $notificationInput['userIds'] = $userIds;
            $notificationInput['notificationData'] = $notificationData;
            $modelNotification->createNotification($notificationInput);
            // end send notification 

            $response['message'] = Yii::$app->params['apiMessage']['comment']['success'];
            return $response;
        } else {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
            return $response;
        }
    }

    /**
     * unlike post
     */

    public function actionUnlike()
    {

        $model = new CommentLike();
        $userId = Yii::$app->user->identity->id;
        $model->scenario = 'create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;

            return $response;
        }


        $commentId = @(int) $model->comment_id;
        $sourceType = @(int) $model->source_type;


        $result = $model->find()->where(['comment_id' => $commentId,'source_type'=>$sourceType,'user_id' => $userId])->one();
        if (isset($result->id)) {
            if ($result->delete()) {
                $response['message'] = Yii::$app->params['apiMessage']['comment']['likeRemoved'];
                return $response;
            } else {

                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors'] = $errors;
                return $response;

            }

        } else {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
            return $response;

        }

    }



    protected function findModel($id)
    {
        if (($model = CommentLike::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}