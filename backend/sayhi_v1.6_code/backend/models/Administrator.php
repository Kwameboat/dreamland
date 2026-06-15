<?php
namespace backend\models;
use app\models\User;


use Yii;

/**
 * This is the model class for table "user".
 *
 * @property int $id
 * @property string $username
 * @property string $auth_key
 * @property string $password_hash
 * @property string|null $password_reset_token
 * @property string $email
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 * @property string|null $verification_token
 */
class Administrator extends User
{
    
    const ROLE_ADMIN = 1;
    const ROLE_SUBADMIN=2;
    const ROLE_CUSTOMER=3;


    const STATUS_DELETED = 0;
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;
    public $password;
    public $confirmPassword;
    
    
   
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username', 'email','name'], 'required'],
            [['status', 'created_at', 'updated_at'], 'integer'],
            [['username','name', 'password_hash', 'email'], 'string', 'max' => 255],
           
            [['username'], 'checkUniqueUsername'],
           [['email'], 'checkUniqueEmail'],
           
            [['password_reset_token'], 'unique'],
            [['password','confirmPassword'], 'required','on'=>'create'],
            [['password'], 'string', 'min' => 6],
            
            [['confirmPassword'], 'compare', 'compareAttribute' => 'password'],
           // [['module_ids'], 'save'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'auth_key' => 'Auth Key',
            'password_hash' => 'Password Hash',
            'password_reset_token' => 'Password Reset Token',
            'email' => 'Email',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'verification_token' => 'Verification Token',
            
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->password_hash = Yii::$app->security->generatePasswordHash($this->password);
            $this->role = self:: ROLE_SUBADMIN;

        }

        $this->updated_at = time();

        return parent::beforeSave($insert);
    }
    
    public function checkUniqueUsername($attribute, $params, $validator)
    {
        if(!$this->hasErrors()){
            if($this->isNewRecord){
                $count= User::find()->where([$attribute=>$this->$attribute])->andWhere(['<>','status',self::STATUS_DELETED])->count();
            }else{
                $count= User::find()->where([$attribute=>$this->$attribute])->andWhere(['<>','id',$this->id])->andWhere(['<>','status',self::STATUS_DELETED])->count();
            }
            
            if($count){
                $this->addError($attribute, 'Username already exist');     
            }
            
        }
       
    }

    

    public function checkUniqueEmail($attribute, $params, $validator)
    {
        if(!$this->hasErrors()){
            if($this->isNewRecord){
                $count= User::find()->where([$attribute=>$this->$attribute])->andWhere(['<>','status',self::STATUS_DELETED])->count();
            }else{
                $count= User::find()->where([$attribute=>$this->$attribute])->andWhere(['<>','id',$this->id])->andWhere(['<>','status',self::STATUS_DELETED])->count();
            }
            
            if($count){
                $this->addError($attribute, 'Email already exist');     
            }
            
        }
       
    }


    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_INACTIVE => 'Inactive');
    }

    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }



    
   

}
