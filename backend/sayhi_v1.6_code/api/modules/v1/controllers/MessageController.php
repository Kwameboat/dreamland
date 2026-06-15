<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use api\modules\v1\models\Message;
use api\modules\v1\models\MessageGroup;
use api\modules\v1\models\Ad;

class MessageController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\message';   
    
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


    public function actionCreate()
    {
        
        $userId    =     Yii::$app->user->identity->id;
        
        $model =   new Message();
        $modelMessageGroup  =   new MessageGroup();
        $modelAd  =   new Ad();
        $model->scenario = 'create';

        $model->load(Yii::$app->getRequest()->getBodyParams(), '');

        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
        
            return $response;
        }



        $groupId  =  (int)$model->group_id;
        $adId  =  (int)$model->ad_id;
      
        if($groupId==0){ /// add gruop

            $adId  =  (int)$model->ad_id;

            $messageGroupResult       =   $modelMessageGroup->find()->where(['ad_id'=>$adId,'sender_id'=>$userId])->one();
            
            if(!$messageGroupResult){

                

                $adResult       =   $modelAd->findOne($adId);
                if($adResult){
                    
                    $modelMessageGroup->ad_id           = $adId;
                    $modelMessageGroup->receiver_id     =  $adResult->user_id;
    
                    if($modelMessageGroup->save(false)){
                        
                        $messageGroupResult  = $modelMessageGroup->findOne($modelMessageGroup->id);
                        
    
                    }
                }

            }
           
        }else{
           $messageGroupResult     =   $modelMessageGroup->findOne($groupId);

        }

        
        if(@$messageGroupResult->sender_id ==$userId){

            $receiverId = $messageGroupResult->receiver_id;
        }else{
            $receiverId = $messageGroupResult->sender_id;
        }
        
        $model->group_id        = $messageGroupResult->id;
       
        $model->receiver_id     = $receiverId;

        if($model->save(false)){

            $response['message']='Message sent successfully';
            $response['group_id']=$messageGroupResult->id;
            return $response; 

        }else{

            $response['message']='Message not sent successfully';
            $response['statusCode']=422;
            return $response; 
        }

    }


    public function actionMessageGroup()
    {
        $userId    =     Yii::$app->user->identity->id;
        $modelMessageGroup  =   new MessageGroup();
        $groupResult =  $modelMessageGroup->getActiveGroup($userId);

        $response['message']='Message active session found successfully';
        $response['group']=$groupResult;
        return $response; 


    }
   

    public function actionMessageHistory()
    {
        $userId    =     Yii::$app->user->identity->id;
        $model  =   new Message();
        
        $last_time = Yii::$app->getRequest()->get('last_time', 0);
        $group_id = Yii::$app->getRequest()->get('group_id', 0);
        $model->scenario = 'messageHistory';

       
        $model->load(Yii::$app->getRequest()->get(), '');
        
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
        
            return $response;
        }


        $result = $model->find()
        ->where(['group_id'=>$group_id])
        ->andWhere(['>=','created_at',$last_time])->all();



        $response['message']='Message list found successfully';
        $response['messages']=$result;
        $response['last_time']=time();

        
        return $response; 


    }
   



}


