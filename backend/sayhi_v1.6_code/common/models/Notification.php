<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;

//use api\modules\v1\models\Package;
//use api\modules\v1\models\Ad;
use common\models\User;

class Notification extends \yii\db\ActiveRecord
{



    /*const TYPE_NEW_FOLLOWER=1;
    const TYPE_NEW_COMMENT=2;*/
    
    
    /**
     * {@inheritdoc}
     */

    


    public static function tableName()
    {
        return 'notification';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
           
            [['id','type','user_id','created_at','reference_id','read_status'], 'integer'],
            [['title','message'], 'string', 'max' => 256]

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            
        ];
    }
   
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
                  
        }
        
        return parent::beforeSave($insert);
    }


   

    public function createNotification($options)
    {
            
        
        
        $modelUser             =   new User();
        $referenceId               = @$options['referenceId'];
        
        
        $userIds               = $options['userIds'];
        $notificationData      = $options['notificationData'];
        $isSaveList            = (isset($options['isSaveList'])) ? $options['isSaveList'] : true;
        $title                 = $notificationData['title'];
        $body                  = $notificationData['body'];
        $type                  = @$notificationData['type'];
        

        
        $userIds= array_unique($userIds);
        $userDeviceIds=[];
        
        $resultUsers = $modelUser->find()->select(['id','device_token','email','is_push_notification_allow'])->where(['IN','id',$userIds])->all();
        foreach($resultUsers as $resultUser){
           
            if($resultUser->device_token &&  $resultUser->is_push_notification_allow){
                //$userDeviceIds[] = $resultUser->device_token;
                $userDeviceIds[]=['deviceId'=>$resultUser->device_token,'receiverId'=>$resultUser->id];
                
            }
            if($isSaveList){
                
                $modelNotification                  =   new Notification();
                $modelNotification->user_id         =   $resultUser->id;
                $modelNotification->type            =   $type;
                $modelNotification->title           =   $title;
                $modelNotification->message         =   $body;
                $modelNotification->reference_id        =   $referenceId;
               
               $modelNotification->save(false);
            }


        }

       
        $dataPush['title']	        	        	=	$title;
        $dataPush['body']		                	=	$body;
        $dataPush['data']['notification_type']		=	$type;
        $dataPush['data']['reference_id']		    =	$referenceId;

        //$dataPush['data']['seller_order_id']		=	$sellerOrderId;
        /*if($orderCustomizationRequestId){
            $dataPush['data']['orderCustomizationRequestId']		=	$orderCustomizationRequestId;
        }*/
       // $deviceTokens    					        =    $userDeviceIds;
        /*$deviceTokens=[];
        foreach($userDeviceIds as $deviceToken){
            $deviceTokens[] = $deviceToken;
        }*/
        if(count($userDeviceIds)>0){
            foreach($userDeviceIds as $record){
                $deviceTokens=[];

                $receiverId = $record['receiverId'];
                $deviceTokens[] = $record['deviceId'];
                
                $dataPush['data']['receiver_id']	 =	$receiverId;
                Yii::$app->pushNotification->sendPushNotification($deviceTokens, $dataPush);
            }
        }

        if (isset($options['userIds']) && is_array($options['userIds']) && class_exists(\common\components\DreamlandPushService::class)) {
            Yii::$app->dreamlandPush->sendWebPush(
                $options['userIds'],
                $title,
                $body,
                (string) ($options['url'] ?? '/')
            );
        }
       

        
       //Yii::$app->pushNotification->sendPushNotification($deviceTokens, $dataPush);
       
        

    }

    
    public function replaceContent($content,$replaceData)
    {
        foreach($replaceData as $key => $value){
            $content = str_replace('{{'.$key.'}}',$value,$content);
        }
        return $content;
    }
    
    
    

    

}
