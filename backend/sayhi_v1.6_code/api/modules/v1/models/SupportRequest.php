<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;


class SupportRequest extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
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
            [['request_message','reply_message',  'name','email','phone'], 'string'],
            [['request_message'], 'required','on'=>'create'],
           

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
      
    }
   
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->created_by =   Yii::$app->user->identity->id;
          
        }else{
            $this->updated_at = time();
            $this->updated_by =   Yii::$app->user->identity->id;

        }

        
        return parent::beforeSave($insert);
    }
    

}
