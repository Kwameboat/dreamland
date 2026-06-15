<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use api\modules\v1\models\Notification;
use yii\data\ActiveDataProvider;

class NotificationController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\notification';   
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
            'except'=>['ad-search'],
            'authMethods' => [
                HttpBearerAuth::className()
            ],
        ];
        return $behaviors;
    }
 


    public function actionIndex(){
        
         $userId  = Yii::$app->user->identity->id;
         $model =  new Notification();
         $query = $model->find()->where(['user_id'=>$userId])
         ->joinWith([
            'createdByUser' => function ($query) {
                $query->select(['name', 'username', 'email', 'image','cover_image', 'id', 'is_chat_user_online', 'chat_last_time_online', 'location', 'latitude', 'longitude']);
            }
        ])
           ->orderBy(['id'=>SORT_DESC]);
            

         $dataProvider = new ActiveDataProvider([
                 'query' => $query,
                 'pagination' => [
                     'pageSize' => 200,
                 ]
         ]);
    

        
         $response['message']='Ok';
         $response['notification']=$dataProvider;
        
         return $response;

    }


    public function actionInformation(){
        
        $userId  = Yii::$app->user->identity->id;
        
        $model =  new Notification();
        $unreadMessageCount = $model->find()->where(['user_id'=>$userId,'read_status'=>Notification::READ_STATUS_NO])->count();

       
        $response['message']='Ok';
        $response['unread_notification']=$unreadMessageCount;
       
        return $response;


   }
   /**
     * send invitation
     */
    public function actionUpdateReadStatus()
    {
        $userId = Yii::$app->user->identity->id;



        $model = new Notification();
        $model->scenario = 'readStatus';

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');

            if (!$model->validate()) {
                $response['statusCode'] = 422;
                $response['errors'] = $model->errors;
                return $response;
            }

           if($model->is_read_all){ // update read all
                $result = $model->updateAll(['read_status' => Notification::READ_STATUS_YES], ['read_status' => Notification::READ_STATUS_NO,'user_id'=>$userId]);

           }else{
             $result = $model->find()->where(['id'=>$model->id,'user_id'=>$userId])->one();
             if($result){
                $result->read_status =  Notification::READ_STATUS_YES;
                $result->save(false);
             }
           }
            $response['message'] = 'Status updated successfully';
            return $response;


        }


    }


}



