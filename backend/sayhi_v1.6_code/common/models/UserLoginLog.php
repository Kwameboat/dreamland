<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;


/**
 * This is the model class 
 *
 */
class UserLoginLog extends \yii\db\ActiveRecord
{
    const DEVICE_TYPE_ANDROID=1;
    const DEVICE_TYPE_IOS=2;
    const DEVICE_TYPE_WEB=3;

    const LOGIN_MODE_MANUALLY=1;
    const LOGIN_MODE_PHONE_NUMBER=2;
    const LOGIN_MODE_SOCIAL=3;

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_login_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','user_id','device_type','login_mode','created_at'], 'integer'],
            [['device_model','device_os_version','device_app_release_version','release_version','login_ip','login_location'], 'string'],
            
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app','ID'),
            'user_id' => Yii::t('app','User'),
            'created_at' => Yii::t('app','Logged At')
            
        ];
    }
    public function getDeviceTypeString()
    {
        if($this->device_type==$this::DEVICE_TYPE_ANDROID){
           return 'Android';    
        }else if($this->device_type==$this::DEVICE_TYPE_IOS){
            return 'iOS';    
        }else if($this->status==$this::DEVICE_TYPE_WEB){
            return 'Web';    
        }
       
    }
    public function getLoginModeString()
    {
        if($this->login_mode==$this::LOGIN_MODE_MANUALLY){
           return 'Manually';    
        }else if($this->login_mode==$this::LOGIN_MODE_PHONE_NUMBER){
            return 'Phone Number';    
        }else if($this->login_mode==$this::LOGIN_MODE_SOCIAL){
            return 'Social';    
        }
       
    }
}
