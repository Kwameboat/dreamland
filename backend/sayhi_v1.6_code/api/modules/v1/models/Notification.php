<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\Competition;
use api\modules\v1\models\Post;
use api\modules\v1\models\User;
use api\modules\v1\models\Comment;
use api\modules\v1\models\PostComment;
use api\modules\v1\models\CampaignComment;
use api\modules\v1\models\PickleballTeamPlayer;
use api\modules\v1\models\Club;
use api\modules\v1\models\Collaborate;

class Notification extends \yii\db\ActiveRecord
{
    const READ_STATUS_NO=0;
    const READ_STATUS_YES=1;
    
    
    public $is_read_all;
    
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
           
            [['id','type','user_id','created_at','reference_id','read_status','created_by','is_read_all'], 'integer'],
            [['title','message'], 'string', 'max' => 256],
            [['id','is_read_all'], 'required','on'=>['readStatus']],
            [['is_read_all'], 'safe'],
            

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
            $this->created_by  =   Yii::$app->user->identity->id;
                  
        }
        
        return parent::beforeSave($insert);
    }


    public function fields()
    {
        $fields = parent::fields();

     //  $fields[] = 'userLocation';
        return $fields;
    }

    public function extraFields()
    {
         return ['createdByUser','refrenceDetails'];
    }
    

    public function createNotification($options)
    {
            
        $modelUser             =   new User();
        $referenceId           = @$options['referenceId'];
        $createdBy             = (int)@$options['createdBy'];
        
        
        $userIds               = $options['userIds'];
        $notificationData      = $options['notificationData'];
        $isFollowing            = (int)@$options['isFollowing'];
        $isSaveList            = (isset($options['isSaveList'])) ? $options['isSaveList'] : true;
        $title                 = $notificationData['title'];
        $body                  = $notificationData['body'];
        $type                  = @$notificationData['type'];
        
        
        

        

        if($userIds){
            $userIds= array_unique($userIds);
        }
       
        
        $userDeviceIds=[];
        
        $resultUsers = $modelUser->find()->select(['id','device_token','email','is_push_notification_allow','like_push_notification_status','comment_push_notification_status'])->where(['IN','id',$userIds])->all();
        foreach($resultUsers as $resultUser){
            
            if($resultUser->device_token &&  $resultUser->is_push_notification_allow){
               
             

                $isSend=false;
                if($type==2){ // comment
                    if($resultUser->comment_push_notification_status==User::NOTIFICATION_ALL){
                        $isSend=true;
                    }else if($resultUser->comment_push_notification_status==User::NOTIFICATION_FOLLOWING && $isFollowing){
                        $isSend=true;
                    }
                }else if($type==3){ //like
                    
                    if($resultUser->like_push_notification_status==User::NOTIFICATION_ALL){
                        $isSend=true;
                    }else if($resultUser->like_push_notification_status==User::NOTIFICATION_FOLLOWING && $isFollowing){
                        $isSend=true;
                    }
                   
                    
                }else{
                    $isSend=true;
                }
              
                if($isSend){
                  
                    if($resultUser->device_token){
                       // $userDeviceIds[] = $resultUser->device_token;
                        $userDeviceIds[]=['deviceId'=>$resultUser->device_token,'receiverId'=>$resultUser->id];
                    }
                }
                
                
                
            }

            
            if($isSaveList){
                
                $modelNotification                  =   new Notification();
                $modelNotification->user_id         =   $resultUser->id;
                $modelNotification->type            =   $type;
                $modelNotification->title           =   $title;
                $modelNotification->message         =   $body;
                $modelNotification->reference_id        =   $referenceId;
               /* if($createdBy>0){
                    $modelNotification->createdBy        =   $referenceId;
                }*/
               
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
        //$deviceTokens    					        =    $userDeviceIds;
       
        
        if(count($userDeviceIds)>0){
            foreach($userDeviceIds as $record){
                $deviceTokens=[];

                $receiverId = $record['receiverId'];
                $deviceTokens[] = $record['deviceId'];
                $dataPush['data']['receiver_id']	 =	$receiverId;
                return Yii::$app->pushNotification->sendPushNotification($deviceTokens, $dataPush);
                
            }

           //return Yii::$app->pushNotification->sendPushNotification($deviceTokens, $dataPush);
        }
       
        

    }

    
    public function replaceContent($content,$replaceData)
    {
        foreach($replaceData as $key => $value){
            $content = str_replace('{{'.$key.'}}',$value,$content);
        }
        return $content;
    }

    public function getRefrenceDetails()
    {
        $type= $this->type;
        $referenceId  = $this->reference_id;
        
        
        if($type==1){
              return User::find()->where(['id'=>$referenceId])->select(['id','name','username','email','bio','description','image','country_code','phone','country','city','sex'])->one();;
        }
        elseif($type==2){
            return Post::find()->select('id,user_id ,type,title')->where(['id'=>$referenceId])->one();
        }
       elseif($type==3){
           return Post::find()->select('id,user_id ,type,title')->where(['id'=>$referenceId])->one();
       }    
        elseif($type==4){
            return Competition::find()->where(['id'=>$referenceId])->one();
        }
        elseif($type==5){
            return Competition::find()->where(['id'=>$referenceId])->one();
        }
        elseif($type==11 || $type==12 ){
            return Club::find()->where(['id'=>$referenceId])->one();
        }
        
        elseif($type==21){ ///post comment
            return PostComment::find()->where(['id'=>$referenceId])->one();
        }
        
        elseif($type==22){//campaign comment
            return CampaignComment::find()->where(['id'=>$referenceId])->one();
        }
        elseif($type==23){ //coupon comment
            return Comment::find()->where(['id'=>$referenceId])->one();
        }
        elseif($type==30 || $type==31 || $type==32){ //match notification
            return PickleballTeamPlayer::find()->where(['id'=>$referenceId])->one();
        }  elseif($type==40 || $type==41 || $type==42){ //collabetration
            return Collaborate::find()->where(['id'=>$referenceId])->one();
        }
        
        
    } 

    public function getUserDetails($userId)
    {
       
        return User::find()->where(['id'=>$userId])->select(['id','name','username','email','bio','description','image','country_code','phone','country','city','sex'])->one();
    } 

    public function getCreatedByUser()
    {
        
        return $this->hasOne(User::className(), ['id'=>'created_by']);
      
    } 
    

}
