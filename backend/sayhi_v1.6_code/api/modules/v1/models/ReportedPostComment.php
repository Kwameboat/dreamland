<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;

class ReportedPostComment extends \yii\db\ActiveRecord
{
    const STATUS_DELETED=0;
    const STATUS_PENDING=1;
    const STATUS_ACEPTED=2;
    const STATUS_REJECTED=3;

    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'reported_post_comment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'id','user_id','post_comment_id'], 'integer'],
            [['post_comment_id'], 'required', 'on'=>'create']
            

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
            'post_comment_id' => Yii::t('app', 'Post comment'),
            'created_at'=> Yii::t('app', 'Reported At'),
            
        ];
    }
   
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->user_id =   Yii::$app->user->identity->id;
          
        }else{
            $this->resolved_at = time();
            
        }

        
        return parent::beforeSave($insert);
    }
    

    public function getStatus()
    {
       if($this->status==$this::STATUS_DELETED){
           return 'Deleted';
       }else if($this->status==$this::STATUS_PENDING){
           return 'Pending';    
       } else if($this->status==$this::STATUS_ACEPTED){
        return 'Acepted';    
        } else if($this->status==$this::STATUS_REJECTED){
            return 'Rejected';    
            }
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }
    

    

}
