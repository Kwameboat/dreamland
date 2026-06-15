<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use common\models\User;
use yii\db\Command;

class BlockedIp extends \yii\db\ActiveRecord
{
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'blocked_ip';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','created_by','created_at'], 'integer'],
            [['description','ip_address'], 'string'],
            [['ip_address'], 'required', 'on'=>['create','update']],
            [['ip_address'], 'checkAlreadyBan', 'on' => ['create','update']],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'ip_address' => Yii::t('app', 'IP Address'),
            'created_at'=>Yii::t('app', 'Blocked At'),
        ];
    }
   
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->created_by =   Yii::$app->user->identity->id;
        }
        return parent::beforeSave($insert);
    }

    public function fields()
    {
        $fields = parent::fields();
        return $fields;
    }

    public function checkAlreadyBan($attribute, $params, $validator)
    {
        if(!$this->hasErrors()){
            if($this->isNewRecord){
                $count= BlockedIp::find()->where([$attribute=>$this->$attribute])->count();
            }else{
                $count= BlockedIp::find()->where([$attribute=>$this->$attribute])->andWhere(['<>','id',$this->id])->count();
            }
            
            if($count){
                $this->addError($attribute, 'IP already  blocked');     
            }
            
        }
       
    }

    public function logoutUserWithLastLoginIp($ipAddress)
    {
        $command = Yii::$app->db->createCommand('
        SELECT ld.*  FROM user_login_log ld
        JOIN (
            SELECT user_id as inner_user_id, MAX(id) as last_login_id FROM user_login_log
            GROUP BY inner_user_id
        ) last_login On ld.id = last_login.last_login_id
        where ld.login_ip = "'.$ipAddress.'"'
        
        );
        $result = $command->queryAll();
        foreach($result as $userLog){
            $blockedUserId = $userLog['user_id'];
            $resultUser = User::findOne($blockedUserId);
            if($resultUser){
                $resultUser->auth_key = NULL;
                $resultUser->device_token = NULL;
                $resultUser->device_token_voip_ios = NULL;
                $resultUser->is_chat_user_online = 0;
                $resultUser->save(false);
            }
        }
       
    }

}
