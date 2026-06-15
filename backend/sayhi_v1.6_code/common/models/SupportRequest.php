<?php
namespace common\models;
//use common\models\User;
use Yii;

class SupportRequest extends \yii\db\ActiveRecord
{
    
    const STATUS_ACTIVE=10;
    const STATUS_BLOCKED=9;
    const STATUS_DELETED=0;

    const COMMON_NO=0;
    const COMMON_YES=1;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'support_request';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'id','user_id','is_reply'], 'integer'],
            [['name','email','phone','request_message','reply_message'], 'string'],
            [['reply_message'], 'required','on'=>'reply'],
            
          //  [['title', 'image','start_date', 'end_date'], 'string', 'max' => 100],
            
           

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => Yii::t('app', 'User'),
            'status' => Yii::t('app', 'Status'),
            'is_reply' => Yii::t('app', 'Is Reply'),
            'request_message' => Yii::t('app', 'Request Message'),
            'reply_message' => Yii::t('app', 'Reply Messge'),
            'updated_at' => Yii::t('app', 'Replied at'),
            
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->created_by = Yii::$app->user->identity->id;

        } else {
            $this->updated_at = time();
            $this->updated_by = Yii::$app->user->identity->id;

        }

        return parent::beforeSave($insert);
    }
   
    
    public function getStatusString()
    {
       if($this->status==$this::STATUS_BLOCKED){
           return 'Blocked';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }
    }

    public function getIsReplyString()
    {
       if($this->is_reply==$this::COMMON_NO){
           return 'Pending';
       }else if($this->is_reply==$this::COMMON_YES){
           return 'Replied';    
       }
    }


    

    public function getIsReplyButton()
    {
    
           
            if($this->is_reply){
                return'<button type="button" class="btn btn-sm active_btn">'.Yii::t('app','Replied').'</button>';      
                
            }else{
                return'<button type="button" class="btn btn-sm pending_btn">'.Yii::t('app','Pending').'</button>'; 
            }


       
    }



    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);

    }

    public function getTotalSupportRequest()
    {
        $totalRequest =0;
        $totalPendingRequest = 0;
        $tatalPendingReqPercentage =0;    
        $totalRequest = SupportRequest::find()->where(['!=','status',SupportRequest::STATUS_DELETED])->count();

        
        $totalPendingRequest = SupportRequest::find()->where(['!=','status',SupportRequest::STATUS_DELETED])->andWhere(['is_reply'=>SupportRequest::COMMON_NO])->count();

        if($totalRequest>0){
            $tatalPendingReqPercentage = (($totalRequest-$totalPendingRequest)/$totalRequest)*100;
        }else{
            $tatalPendingReqPercentage=100;
        }
        

       
        $response = array(
            "totalSupport" =>$totalRequest,
            'totalPendingSupport'=>$totalPendingRequest,
            'percentage'=>$tatalPendingReqPercentage
        );
        return $response;
    }


}
