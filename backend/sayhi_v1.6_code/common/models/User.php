<?php
namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\web\ForbiddenHttpException;
use frontend\models\Package;
use frontend\models\Subscription;


/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $verification_token
 * @property string $email
 * @property string $auth_key
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 0;
    const STATUS_PENDING = 1;
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;

    const ROLE_ADMIN = 1;
    const ROLE_SUBADMIN = 2;
    const ROLE_CUSTOMER = 3;
    const ROLE_AGENT = 4;
    

    const COMMON_YES = 1;
    const COMMON_NO = 0;

    public $imageFile;

    public $otp;
    public $password;
   

    

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [

            ['status', 'default', 'value' => self::STATUS_INACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_DELETED]],
            [['imageFile'], 'file', 'skipOnEmpty' => false, 'on' => 'updateProfileImage'],

            [['status', 'created_at', 'updated_at', 'country_id', 'is_verified','user_verification_id'], 'integer'],
            [['username', 'name', 'password_hash', 'password_reset_token', 'email', 'verification_token', 'address', 'city', 'postcode', 'website','bio','dob','description'], 'string', 'max' => 255],
            [['auth_key'], 'string', 'max' => 32],
            
           
           // [['password_reset_token'], 'unique'],
           // [['password'], 'string', 'min' => 6],
           // [['confirmPassword'], 'compare', 'compareAttribute' => 'password'],

            [['name', 'email'], 'required', 'on' => 'updateProfile'],
            [['email'], 'checkUniqueEmail', 'on' => 'updateProfile'],
            [['phone', 'country_code'], 'required', 'on' => 'updateMobile'],
            [['phone'], 'string', 'min' => 10, 'max' => 12],
            [['otp'], 'safe'],
            [['password'], 'string', 'min' => 6]
          
            
           



        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app','ID'),
            'username' => Yii::t('app','Username'),
            'auth_key' => Yii::t('app','Auth Key'),
            'password_hash' => Yii::t('app','Password'),
            'password_reset_token' => Yii::t('app','Password Reset Token'),
            'email' => Yii::t('app','Email'),
            'status' => Yii::t('app','Status'),
            'created_at' => Yii::t('app','Created At'),
            'updated_at' => Yii::t('app','Updated At'),
            'verification_token' => Yii::t('app','Verification Token'),
            'country_id' => Yii::t('app','Country'),
            'name' => Yii::t('app','Name'),
            'description' => Yii::t('app','Description'),
            
        ];
    }


    public function beforeSave($insert)
    {
        if ($insert) {

            $this->created_at = time();
            //$this->password_hash = Yii::$app->security->generatePasswordHash($this->password);
        }

        $this->updated_at = time();

        return parent::beforeSave($insert);
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
                $this->addError($attribute, Yii::t('app','Email already exist'));     
            }
            
        }
       
    }
    public function checkPageAccess()
    {
        if(Yii::$app->user->identity->role==User::ROLE_SUBADMIN && Yii::$app->params['siteMode']==3  ){
        
            throw new ForbiddenHttpException('You are not allowed to take this action in demo');
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);

        //return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findByUsernameEmail($username)
    {

        return static::find()->where(['status' => self::STATUS_ACTIVE])
            ->andWhere(['or', ['username' => $username], ['email' => $username]])->one();

    }

    /**
     * Finds user by email
     *
     * @param string $username
     * @return static|null
     */
    public static function findByEmail($email)
    {
        return static::findOne(['email' => $email, 'status' => self::STATUS_ACTIVE, 'role' => self::ROLE_CUSTOMER]);
    }

    public static function findByFb($fbId)
    {
        //return static::findOne(['email' => $email, 'status' => self::STATUS_ACTIVE,'role'=>self::ROLE_CUSTOMER]);
        return static::find()->where(['facebook' => $fbId, 'role' => self::ROLE_CUSTOMER])->andWhere(['<>', 'status', self::STATUS_DELETED])->one();
    }

    public static function findByGoogle($socialId)
    {
        return static::find()->where(['googleplus' => $socialId, 'role' => self::ROLE_CUSTOMER])->andWhere(['<>', 'status', self::STATUS_DELETED])->one();
    }


  
    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds user by verification email token
     *
     * @param string $token verify email token
     * @return static|null
     */
    public static function findByVerificationToken($token)
    {
        return static::findOne([
            'verification_token' => $token,
            'status' => self::STATUS_INACTIVE,
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return bool
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getImageThumb()
    {
        $image = $this->image;
        if (empty($this->image)) {
            $image = 'default.png';
        }
        return Yii::$app->urlManagerFrontend->baseUrl . '/uploads/user/thumb/' . $image;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Generates new token for email verification
     */
    public function generateEmailVerificationToken()
    {
        $this->verification_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    public function checkSocialUser($input)
    {

        $user = '';
        if ($input['type'] == 1) {
            $user = $this->findByFb($input['socialId']);

        }
        if ($input['type'] == 2) {
            $user = $this->findByGoogle($input['socialId']);

        }

        if ($user) {
            // login/
            $output['status'] = "success";
            $output['message'] = "success";
            $output['id'] = $user->id;

            return $output;

        } else {

            $name = $input['name'];
            $email = $input['email'];
            $socialId = $input['socialId'];

            if (!$name) {
                $name = 'Guest';
            }

            $userModel = new User();
            $userModel->name = $name;
            $userModel->email = $email;
            if ($input['type'] == 1) {
                $userModel->facebook = $socialId;
            }else if ($input['type'] == 2) {
                $userModel->googleplus = $socialId;
            }
            $userModel->online = 1;

            if ($userModel->save(false)) {

                $modelPackage = new Package();
                $defaultPackage = $modelPackage->getDefaultPackage();
                if ($defaultPackage) {
                    $userModel->package_id = $defaultPackage->id;
                }
                if ($userModel->save(false)) {
                    $modelSubscription = new Subscription();
                    $expiryDate = $modelSubscription->getExpirtyDate($defaultPackage->term);

                    $modelSubscription->user_id = $userModel->id;
                    $modelSubscription->package_id = $defaultPackage->id;
                    $modelSubscription->title = $defaultPackage->name;
                    $modelSubscription->term = $defaultPackage->term;
                    $modelSubscription->amount = $defaultPackage->price;
                    $modelSubscription->ad_limit = $defaultPackage->ad_limit;
                    $modelSubscription->ad_remaining = $defaultPackage->ad_limit;
                    $modelSubscription->payment_mode = Payment::PAYMENT_MODE_PAYPAL;
                    $modelSubscription->expiry_date = $expiryDate;
                    $modelSubscription->save(false);

                }

                $output['status'] = "success";
                $output['message'] = "success";
                $output['id'] = $userModel->id;

                return $output;

            } else {

                $output['status'] = "error";
                $output['message'] = "something is whrong";
                $output['id'] = $userModel->id;
                return $output;

            }

        }

    }


   
    public function getIsVerifiedString()
    {
        if($this->is_verified==$this::COMMON_YES){
           return 'Yes';    
        }else {
            return 'No';    
        }
       
    }

    public function getFollowerUser()
    {
        return $this->hasMany(Follower::className(), ['user_id'=>'id']);
    }

    
    public function getFollowingUser()
    {
        return $this->hasMany(Follower::className(), ['follower_id'=>'id']);
    }

    public function getIsFollowing()
    {
        if(isset(Yii::$app->user->identity)){
            return $this->hasOne(Follower::className(), ['user_id'=>'id'])->andOnCondition(['follower.follower_id' => Yii::$app->user->identity->id]);
        }else{
            return null;
        }
        
        
    }
    public function getImageUrl(){
        
        $image = $this->image;
        if(empty($this->image)){
            $image  ='default.png';
        }
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_USER,$image);

        //return Yii::$app->params['pathUploadUser'].'/'.$image;
        
        
    }
    


}
