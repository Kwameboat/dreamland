<?php
namespace api\modules\v1\controllers;

use api\modules\v1\models\Competition;
use api\modules\v1\models\CompetitionUser;
use api\modules\v1\models\HashTag;
use api\modules\v1\models\Notification;
use api\modules\v1\models\UserVerification;
use api\modules\v1\models\UserVerificationDocument;
use api\modules\v1\models\User;

use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\imagine\Image;
use yii\rest\ActiveController;
use yii\web\UploadedFile;


class UserVerificationController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\userVerification';
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

    public function actionIndex()
    {
        
        $userId = Yii::$app->user->identity->id;
        
        $model = new UserVerification();

        
        
        $query = $model->find()->where(['user_id'=>$userId]);
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' =>false
        ]);

        
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['verification']= $dataProvider;
        return $response;
       
    }

    public function actionCreate()
    {
        $userId = Yii::$app->user->identity->id;
        $model = new UserVerification();

        $model->scenario = 'create';

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if (!$model->validate()) {
                $response['statusCode'] = 422;
                $response['errors'] = $model->errors;
                return $response;
            }

            $isPending = (int)$model->find()->where(['user_id'=>$userId,'status'=>UserVerification::STATUS_PENDING])->count();
            if($isPending){
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['userVerification']['alreadyPendingVerification'];
                $response['errors'] = $errors;
                return $response;

            }


          
            if ($model->save()) {

                $userVerificationId = $model->id;



                if ($model->document) {
                    $modeluserVerificationDocument = new userVerificationDocument();
                    $modeluserVerificationDocument->updateDocument($userVerificationId, $model->document);
                }

                $response['message'] = Yii::$app->params['apiMessage']['userVerification']['created'];
                $response['user_verification_id'] = $model->id;
           
                return $response;
            } else {

                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors'] = $errors;
                return $response;

            }

        }

    }


    public function actionCancel()
    {

        $model = new UserVerification();
        $userId = Yii::$app->user->identity->id;

        $model->scenario = 'cancel';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;

            return $response;
        }

        $verificationId = @(int) $model->id;

        $totalCount = $model->find()->where(['id' => $verificationId, 'user_id' => $userId, 'status' => UserVerification::STATUS_PENDING])->count();
        if(!$totalCount) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
            $response['errors'] = $errors;
            return $response;

        }
        $modelVerification =   UserVerification::findOne($verificationId);

        $modelVerification->user_message = $model->user_message;
        $modelVerification->status = UserVerification::STATUS_CANCELLED;
        if ($modelVerification->save(false)) {
            $response['message'] = Yii::$app->params['apiMessage']['userVerification']['cancelled'];
            return $response;
        } else {

            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
        }
    }

    
    

  
    /*
    public function actionView($id)
    {

        $model = new PostSearch();

        $result = $model->find()->where(['post.id'=>$id])
        ->joinWith(['user' => function($query){
            $query->select(['name','username','email','image','id','is_chat_user_online','chat_last_time_online','location','latitude','longitude']);
        }])
        ->joinWith(['clubDetail.createdByUser' => function($query){
            $query->select(['name','username','email','image','id','is_chat_user_online','chat_last_time_online','location','latitude','longitude']);
        }])
        
        ->one();
        $response['message'] = Yii::$app->params['apiMessage']['post']['listFound'];
        $response['post'] = $result;
        return $response;

    }
    public function actionDelete($id)
    {
        $userId = Yii::$app->user->identity->id;
        
        $model =   Post::find()->where(['id'=>$id,'user_id'=>$userId])->one();

      

        if( $model){
            $model->status = Post::STATUS_DELETED;
            if($model->save(false)){
                
                $response['message']=Yii::$app->params['apiMessage']['post']['deleted'];
             
                return $response; 
            }else{
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;
            }
        }
      
    }
    */


    protected function findModel($id)
    {
        if (($model = UserVerification::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}
