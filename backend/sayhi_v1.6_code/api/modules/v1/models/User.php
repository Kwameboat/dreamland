<?php
namespace api\modules\v1\models;
use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use api\modules\v1\models\Follower;
use api\modules\v1\models\Post;
use api\modules\v1\models\ReportedUser;
use api\modules\v1\models\CompetitionUser;
use api\modules\v1\models\CompetitionPosition;
use api\modules\v1\models\BlockedUser;
use api\modules\v1\models\UserLiveHistory;
use api\modules\v1\models\GiftHistory;
use api\modules\v1\models\UserSetting;
use api\modules\v1\models\Setting;
use api\modules\v1\models\UserInterest;
use api\modules\v1\models\UserLanguage;
use api\modules\v1\models\ProfileCategoryType;
use api\modules\v1\models\Club;
use api\modules\v1\models\MentionUser;
use api\modules\v1\models\FeatureList;
use api\modules\v1\models\PickleballTeamPlayer;
use api\modules\v1\models\SubscriptionPlanUser;
use api\modules\v1\models\SubscriptionPlanSubscriber;

/**
 * User model

 */
class User extends ActiveRecord implements IdentityInterface
{
    const ROLE_ADMIN = 1;
    const ROLE_SUBADMIN = 2;
    const ROLE_CUSTOMER = 3;
    const ROLE_AGENT=4;

    const STATUS_DELETED = 0;
    const STATUS_PENDING = 1;
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;

    const IS_PHONE_VERIFIED_NO = 0;
    const IS_PHONE_VERIFIED_YES = 1;

    const IS_EMAIL_VERIFIED_NO = 0;
    const IS_EMAIL_VERIFIED_YES = 1;

    const VERIFICATION_WITH_EMAIL = 1;
    const VERIFICATION_WITH_PHONE = 2;

    const NOTIFICATION_OFF = 0;
    const NOTIFICATION_ALL = 1;
    const NOTIFICATION_FOLLOWING = 2;

    const PROFILE_VISIBILITY_PUBLIC =1;
    const PROFILE_VISIBILITY_PRIVATE =2;

    const COMMON_NO = 0;
    const COMMON_YES = 1;


    public $password;
    public $old_password;

    public $locations;
    public $imageFile;
    public $social_type;
    public $social_id;
    public $verify_token;
    public $otp;

    public $token;
    public $userStory;
    public $interest_id;
    public $language_id;

    // public $verification_with;


    /**
     * @inheritdoc
     */
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user}}';
    }


    /**
     * @inheritdoc
     */
    public static function primaryKey()
    {
        return ['id'];
    }


    public function rules()
    {
        return [

            ['status', 'default', 'value' => self::STATUS_INACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE,self::STATUS_PENDING, self::STATUS_INACTIVE, self::STATUS_DELETED]],
            [['device_token', 'device_token_voip_ios', 'description', 'bio', 'country_code', 'phone', 'dob', 'country', 'city', 'paypal_id', 'socket_id', 'location', 'latitude', 'longitude', 'occupation', 'qualification', 'drinking_habit', 'religion', 'color', 'height', 'state', 'website','image'], 'string'],
            [['sex', 'available_coin', 'is_email_verified', 'is_phone_verified', 'account_created_with', 'is_biometric_login', 'is_push_notification_allow', 'like_push_notification_status', 'comment_push_notification_status', 'is_chat_user_online', 'chat_last_time_online', 'smoke_id', 'marital_status', 'country_id', 'state_id', 'city_id', 'work_experience_month', 'work_experience_year', 'profile_category_type', 'profile_visibility','follower_status','following_status','unique_id','is_show_online_chat_status','passionate','holistic_path'], 'integer'],
            //[['email'], 'email'],
            [['available_balance'], 'number'],
            [['userStory', 'interest_id', 'language_id'], 'safe'],


            [['password'], 'string', 'min' => 6],
            [['name'], 'string', 'min' => 2, 'max' => 50],
            [['username'], 'string', 'min' => 2, 'max' => 30],
            [['email', 'password', 'device_type'], 'required', 'on' => 'login'],
            [['social_type', 'social_id'], 'required', 'on' => 'loginSocial'],
            //[['email'], 'checkUniqueEmailSocial','on'=>'loginSocial'],
            //[['email'], 'required','on'=>'forgotPassword'],
            [['verification_with'], 'required', 'on' => 'forgotPassword'],
            [['username', 'email', 'password', 'device_type','role'], 'required', 'on' => 'register'],
            [['email'], 'checkUniqueEmail', 'on' => 'register'],
            [['username'], 'checkUniqueUsername', 'on' => ['register', 'checkUsername', 'profileUpdate']],
            [['like_push_notification_status', 'comment_push_notification_status'], 'required', 'on' => 'pushNotificationStatusUpdate'],
            [['phone'], 'checkUniquePhone', 'on' => 'register'],
            ['role', 'in', 'range' => [self::ROLE_CUSTOMER, self::ROLE_AGENT],'on'=>'register'],
            [['token', 'otp'], 'required', 'on' => 'verifyRegistrationOtp'],
            [['email'], 'checkUniqueEmail', 'on' => ['profileUpdate']],
            [['otp', 'token'], 'required', 'on' => 'forgotPasswordVerifyOtp'],
            [['password', 'token'], 'required', 'on' => 'forgotPasswordNewPassword'],
            [['token'], 'required', 'on' => 'resendOtp'],

            //[['name'],'required', 'on'=>'profileUpdate'],
            [['paypal_id'], 'required', 'on' => 'profilePaymentDetail'],
            [['phone', 'country_code'], 'required', 'on' => 'updateMobile'],
            [['phone'], 'checkUniquePhone', 'on' => 'updateMobile'],

            [['verify_token', 'otp'], 'required', 'on' => 'verifyMobileOtp'],
            [['otp'], 'checkOtp', 'on' => 'verifyMobileOtp'],
            [['password', 'old_password'], 'required', 'on' => 'changePassword'],
            //[['username'], 'required','on'=>'searchUser'],

            //[['name','locations'],'required', 'on'=>'profileUpdate'],
            // [['imageFile'], 'required', 'on'=>'updateProfileImage'],
            [['imageFile'], 'file', 'skipOnEmpty' => false, 'on' => 'updateProfileImage'],
            [['imageFile'], 'file', 'skipOnEmpty' => false, 'on' => 'updateProfileCoverImage'],
            [['profile_visibility'], 'required', 'on' => 'profileVisibility'],
            [['is_show_online_chat_status','profile_visibility'], 'required','on'=>'showChatOnlineStatus'],     
            [['phone', 'country_code'], 'required', 'on' => 'loginWithMobile'],
            [['phone', 'country_code'], 'required', 'on' => 'registerWithMobile'],
            [['phone', 'country_code'], 'required', 'on' => 'verifyPhonenuberLogin'],
            // [['locations'], 'checkUserLocation','on'=>'profileUpdate'],
            /*   [['locations'], 'filter', 'filter' => function ($value) {
                   try {
                       $result = [];

                       $data = $value;//\yii\helpers\Json::decode($value);
                       $dynamicModel = (new \yii\base\DynamicModel())->addRule(['country_id', 'country_name'], 'required');
                       foreach ($data as $item) {
                           $itemModel = $dynamicModel->validateData($item);
                           if ($itemModel->hasErrors()) {
                               $this->addError('location', reset($itemModel->getFirstErrors()));
                               return null;
                           }
                       }

                       return $value;
                   } catch (\yii\base\InvalidParamException $e) {
                       $this->addError('location', $e->getMessage());
                       return null;
                   }
               }],
               */



        ];
    }
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['auth_key'], $fields['password_hash'], $fields['password_reset_token']);
        $fields['is_reported'] = (function ($model) {
            if (Yii::$app->user->isGuest) {
                return 0;
            }
            return self::safeFlagRelation($model, 'isReported');
        });
        $fields[] = 'picture';
        $fields[] = 'coverImageUrl';
        $fields[] = 'userStory';

        $fields['profileCategoryName'] = (function ($model) {
            try {
                return @$model->profileCategoriesName->name;
            } catch (\Throwable $e) {
                return null;
            }
        });
        $fields['is_like'] = (function ($model) {
            return self::safeFlagRelation($model, 'userLikeByUser');
        });
        $fields['is_match'] = (function ($model) {
            return self::safeFlagRelation($model, 'datingMatchProfile');
        });
        return $fields;
    }

    private static function safeFlagRelation(self $model, string $relation): int
    {
        try {
            return @$model->{$relation} ? 1 : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function extraFields()
    {
        return ['isFollowing', 'isFollower', 'totalFollowing', 'totalFollower', 'totalPost', 'totalActivePost', 'totalWinnerPost', 'totalReel', 'totalClub', 'totalMention', 'userLiveDetail', 'giftSummary','isSubscriptionAllowed','subscriptionPlanUser','subscribedStatus', 'follower', 'following', 'interest', 'userSetting', 'language','pickleballSummary','featureList'];
    }

    public function beforeSave($insert)
    {
        if ($insert) {

            $this->created_at = time();
            $this->unique_id = $this->getNextUniueId();
            $this->setPassword($this->password);
            $this->generateAuthKey();

        } else {
            
            
            $this->updated_at = time();
        }

        return parent::beforeSave($insert);
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
        return static::findOne(['auth_key' => $token]);
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        //return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
        return static::find()->where(['username' => $username])->andWhere(['<>', 'status', self::STATUS_DELETED])->one();
    }



    public static function findByFb($fbId)
    {

        return static::find()->where(['facebook' => $fbId, 'role' => self::ROLE_CUSTOMER])->andWhere(['<>', 'status', self::STATUS_DELETED])->one();
    }
    public static function findByTwitter($twitterId)
    {

        return static::find()->where(['twitter' => $twitterId, 'role' => self::ROLE_CUSTOMER])->andWhere(['<>', 'status', self::STATUS_DELETED])->one();
    }
    public static function findByApple($appleId)
    {

        return static::find()->where(['apple' => $appleId, 'role' => self::ROLE_CUSTOMER])->andWhere(['<>', 'status', self::STATUS_DELETED])->one();
    }

    public static function findByGoogle($socialId)
    {

        return static::find()->where(['googleplus' => $socialId, 'role' => self::ROLE_CUSTOMER])->andWhere(['<>', 'status', self::STATUS_DELETED])->one();
    }

    public static function findByInstagram($socialId)
    {

        return static::find()->where(['instagram' => $socialId, 'role' => self::ROLE_CUSTOMER])->andWhere(['<>', 'status', self::STATUS_DELETED])->one();
    }


    public static function findByEmail($email)
    {

        return static::find()->where(['email' => $email, 'role' => self::ROLE_CUSTOMER])->andWhere(['<>', 'status', self::STATUS_DELETED])->one();
    }

    public static function findByPhone($phone)
    {

        return static::find()->where(['phone' => $phone, 'role' => self::ROLE_CUSTOMER])->andWhere(['<>', 'status', self::STATUS_DELETED])->one();
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
            'status' => self::STATUS_INACTIVE
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
    public function validatePassword($password, $password_hash)
    {
        return Yii::$app->security->validatePassword($password, $password_hash);
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

    public function checkLogin()
    {
        //  print_r($this->email);


        $user = $this->find()->where(['status' => self::STATUS_ACTIVE])
            ->andWhere(
                [
                    'or',
                    ['email' => $this->email],
                    ['username' => $this->email]
                ]
            )
            ->one();
        if ($user) {
            if ($this->validatePassword($this->password, $user->password_hash)) {
                return $user;
            } else {
                return false;
            }

        } else {
            return false;
        }
    }

    /*

    public function checkLogin()
    {
      //  print_r($this->email);
        




      $user = $this->find()->where(['email' => $this->email, 'status' => self::STATUS_ACTIVE])->one();
       if($user){
           if($this->validatePassword($this->password,$user->password_hash)){
            return $user;
           }else{
               return false;
           }
           
       }else{
           return false;
       }
    }*/

    public function checkLoginSocail($input)
    {
        $modelPackage = new Package();

        $user = '';


        if ($input['social_type'] == 'fb') {
            $user = $this->findByFb($input['social_id']);
            if ($user) {
                return ['users_details' => $user];
            }
        }

        if ($input['social_type'] == 'twitter') {
            $user = $this->findByTwitter($input['social_id']);
            if ($user) {
                return ['users_details' => $user];
            }
        }

        if ($input['social_type'] == 'apple') {
            $user = $this->findByApple($input['social_id']);
            if ($user) {
                return ['users_details' => $user];
            }
        }

        if ($input['social_type'] == 'google') {
            $user = $this->findByGoogle($input['social_id']);
            if ($user) {
                return ['users_details' => $user];
            }
        }
        if ($input['social_type'] == 'instagram') {
            $user = $this->findByInstagram($input['social_id']);
            if ($user) {
                return ['users_details' => $user];
            }
        }


        /// now start registration process

        if ($input['email']) {

            $user = $this->findByEmail($input['email']);
            if ($user) {
                return ['users_details' => $user, 'emailAlreadyExist' => true];
            }
        }
        $username = @$input['username'];
        if ($username) {
            $user = $this->findByUsername($username);
            if ($user) {
                return ['users_details' => $user, 'usernameAlreadyExist' => true];
            }
        } else {
            $nameBase = $input['name'];
            if (!$nameBase) {
                if ($input['email']) {
                    $nameBase = $input['email'];
                }
            }
            $username = $this->generateUsername($nameBase);
        }

        $socialType = $input['social_type'];
        $name = $input['name'];
        if (!$name) {
            $name = $username;
        }
        $email = $input['email'];
        $socialId = $input['social_id'];

        // $countryId      =  $input['country_id'];


        /*if(!$name){
            $name ='Guest';
        }*/

        //echo $username;


        $model = new User();
        $model->name = $name;
        $model->username = $username;
        $model->email = $email;

        //  $model->country_id = $countryId;
        $accountCreatedWith = 0;

        if ($socialType == 'fb') {
            $model->facebook = $socialId;
            $accountCreatedWith = 2;
        } else if ($socialType == 'twitter') {
            $model->twitter = $socialId;
            $accountCreatedWith = 3;
        } else if ($socialType == 'apple') {
            $model->apple = $socialId;
            $accountCreatedWith = 4;
        } else if ($socialType == 'google') {
            $model->googleplus = $socialId;
            $accountCreatedWith = 5;
        } else if ($socialType == 'instagram') {
            $model->instagram = $socialId;
            $accountCreatedWith = 6;
        }

        $model->role = User::ROLE_CUSTOMER;
        $model->status = User::STATUS_ACTIVE;
        $model->is_email_verified = User::IS_EMAIL_VERIFIED_YES;
        $model->account_created_with = $accountCreatedWith;
        $model->is_login_first_time = 1;
        $model->password = Yii::$app->security->generateRandomString();


        $defaultPackage = $modelPackage->getDefaultPackage();
        if ($defaultPackage) {
            $model->available_coin = $defaultPackage->coin;
        }


        if ($model->save(false)) {
            /*$modelSubscription = new Subscription();

            $expiryDate = $modelSubscription->getExpirtyDate($defaultPackage->term);

            $modelSubscription->user_id             =  $model->id;
            $modelSubscription->package_id          =  $defaultPackage->id;
            $modelSubscription->title               =  $defaultPackage->name;
            $modelSubscription->term                =  $defaultPackage->term;
            $modelSubscription->amount              =   $defaultPackage->price;
            $modelSubscription->ad_limit            =  $defaultPackage->ad_limit;
            $modelSubscription->ad_remaining        =   $defaultPackage->ad_limit;
            $modelSubscription->payment_mode        =  Payment::PAYMENT_MODE_PAYPAL;
            $modelSubscription->expiry_date         =  $expiryDate;
            $modelSubscription->save(false);
            */
            return [
                'users_details' => $model,
                "login_first_time" => 1
            ];

        } else {

            return false;
        }

    }


    public function generateUsername($fullName = "user")
    {
        if (!$fullName) {
            $fullName = "user";
        }

        $removedMultispace = preg_replace('/\s+/', ' ', $fullName);

        $sanitized = preg_replace('/[^A-Za-z0-9\ ]/', '', $removedMultispace);

        $lowercased = strtolower($sanitized);

        $splitted = explode(" ", $lowercased);

        if (count($splitted) == 1) {

            $username = substr($splitted[0], 0, rand(3, 6)) . rand(11111, 99999);
        } else {
            $username = $splitted[0] . substr($splitted[1], 0, rand(0, 4)) . rand(1111, 9999);
        }



        $result = User::find()->where(['username' => $username])->andWhere(['<>', 'status', self::STATUS_DELETED])->one();

        if ($result) {
            $username = $this->generateUsername($fullName);
        }





        return $username;
    }


    public function getPicture()
    {
        if ($this->image) {
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_USER, $this->image);

            //return Yii::$app->params['pathUploadUser'] ."/".$this->image;

        } else {
            return null;
        }

    }

    public function getCoverImageUrl()
    {
        if ($this->cover_image) {
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_USER, $this->cover_image);
        } else {
            return null;
        }

    }



    /**START valication function custom  */
    public function checkUniqueEmail($attribute, $params, $validator)
    {
        if (!$this->hasErrors()) {
            if ($this->isNewRecord) {
                $count = User::find()->where([$attribute => $this->$attribute])->andWhere(['<>', 'status', self::STATUS_DELETED])->count();
            } else {
                $count = User::find()->where([$attribute => $this->$attribute])->andWhere(['<>', 'id', $this->id])->andWhere(['<>', 'status', self::STATUS_DELETED])->count();
            }

            if ($count) {
                $this->addError($attribute, 'Email already exist');
            }

        }

    }

    /**START valication function custom  */
    public function checkUniqueUsername($attribute, $params, $validator)
    {
        if (!$this->hasErrors()) {
            if ($this->isNewRecord) {
                $count = User::find()->where([$attribute => $this->$attribute])->andWhere(['<>', 'status', self::STATUS_DELETED])->count();
            } else {
                $count = User::find()->where([$attribute => $this->$attribute])->andWhere(['<>', 'id', $this->id])->andWhere(['<>', 'status', self::STATUS_DELETED])->count();
            }

            if ($count) {
                $this->addError($attribute, 'Username already exist');
            }

        }

    }


    /**START valication function custom  */
    public function checkUniqueEmailSocial($attribute, $params, $validator)
    {
        if (!$this->hasErrors()) {


            $count = User::find()->where([$attribute => $this->$attribute])->andWhere(['<>', 'status', self::STATUS_DELETED])->count();

            if ($count) {
                $this->addError($attribute, 'Email already exist');
            }

        }

    }


    public function checkUniquePhone($attribute, $params, $validator)
    {
        if (!$this->hasErrors()) {
            if ($this->isNewRecord) {
                $count = User::find()->where([$attribute => $this->$attribute])->andWhere(['<>', 'status', self::STATUS_DELETED])->count();
            } else {
                $count = User::find()->where([$attribute => $this->$attribute])->andWhere(['<>', 'id', $this->id])->andWhere(['<>', 'status', self::STATUS_DELETED])->count();
            }

            if ($count) {
                $this->addError($attribute, 'Phone already exist');
            }
        }

    }

    public function checkOtp($attribute, $params, $validator)
    {
        if (!$this->hasErrors()) {

            $count = User::find()->where(['verification_token' => $this->$attribute, 'id' => $this->id])->count();

            if ($count == 0) {
                $this->addError($attribute, 'Wrong Otp');
            }
        }

    }





    /**END valication function custom  */

    public function getProfile($id)
    {
        return $this->find()->select(['id','role', 'name', 'username', 'email','unique_id', 'bio', 'status','description', 'image', 'cover_image', 'user.is_verified', 'user.country_code', 'user.phone', 'user.country', 'user.city', 'user.sex', 'user.dob', 'user.paypal_id', 'user.available_balance', 'user.available_coin', 'user.is_biometric_login', 'is_push_notification_allow', 'account_created_with', 'auth_key', 'is_login_first_time','profile_visibility','follower_status','following_status','is_show_online_chat_status','passionate','holistic_path','dreamland_account_type','dreamland_creator_status'])->where(['id' => $id])->one();
    }

    public function getFullProfile($id)
    {
        return $this->find()
            ->select(['user.id', 'user.role','user.name', 'user.username', 'user.email','user.unique_id', 'user.bio', 'user.description', 'user.image', 'user.cover_image', 'user.is_verified', 'user.country_code', 'user.phone', 'user.country', 'user.city', 'user.sex', 'user.dob', 'user.is_biometric_login', 'is_push_notification_allow', 'like_push_notification_status', 'comment_push_notification_status', 'is_chat_user_online', 'chat_last_time_online', 'user.account_created_with', 'user.location', 'user.latitude', 'user.longitude','user.profile_visibility','user.follower_status','user.following_status','user.is_show_online_chat_status','user.passionate','user.holistic_path'])
            ->with([
                'following.followingUserDetail' => function ($query) {
                    $query->select(['id','role', 'name', 'username', 'email', 'unique_id','bio', 'description', 'image', 'country_code', 'phone', 'country', 'city', 'sex']);
                }
            ])
            ->with([
                'follower.followerUserDetail' => function ($query) {
                    $query->select(['id','role', 'name', 'username', 'email','unique_id', 'bio', 'description', 'image', 'country_code', 'phone', 'country', 'city', 'sex']);
                }
            ])
            ->with([
                'userSetting' => function ($query) {
                    $query->select(['id', 'user_id', 'relation_setting']);
                }
            ])
            ->where(['user.id' => $id,'user.role'=>[User::ROLE_CUSTOMER,User::ROLE_AGENT]])->one();
    }
    public function getFullProfileMy($id)
    {
        return $this->find()
            ->select(['user.id','user.role', 'user.name', 'user.username', 'user.email', 'user.unique_id','user.bio', 'user.description', 'user.image', 'user.cover_image', 'user.is_verified', 'user.country_code', 'user.phone', 'user.country', 'user.city', 'user.sex', 'user.dob', 'user.paypal_id', 'user.available_balance', 'user.available_coin', 'user.is_biometric_login', 'is_push_notification_allow', 'like_push_notification_status', 'comment_push_notification_status', 'is_chat_user_online', 'chat_last_time_online', 'account_created_with', 'location', 'latitude', 'longitude', 'height', 'color', 'religion', 'marital_status', 'smoke_id', 'drinking_habit', 'qualification', 'occupation', 'country_id', 'state_id', 'city_id', 'work_experience_month', 'work_experience_year', 'profile_category_type','profile_visibility','follower_status','following_status','is_show_online_chat_status','passionate','holistic_path'])
            ->with([
                'following.followingUserDetail' => function ($query) {
                    $query->select(['id','role', 'name', 'username', 'email','unique_id', 'bio', 'description', 'image', 'country_code', 'phone', 'country', 'city', 'sex','is_show_online_chat_status']);
                }
            ])
            ->with([
                'follower.followerUserDetail' => function ($query) {
                    $query->select(['id', 'role','name', 'username', 'email','unique_id', 'bio', 'description', 'image', 'country_code', 'phone', 'country', 'city', 'sex','is_show_online_chat_status']);
                }
            ])
            ->with([
                'userSetting' => function ($query) {
                    $query->select(['id', 'user_id', 'relation_setting']);
                }
            ])
            ->where(['user.id' => $id,'user.role'=>[User::ROLE_CUSTOMER,User::ROLE_AGENT]])->one();
    }


    public function getIsFollowing()
    {
        $id = Yii::$app->user->identity->id;
        $modelFollower = new Follower();
        $res = $modelFollower->find()->where(['user_id' => $this->id, 'follower_id' => $id])->one();
        if (!empty($res)) {
            if ($res['type'] == Follower::FOLLOW_REQUEST) {
                return 2;
            } elseif ($res['type'] == Follower::FOLLOW_REQUEST_ACCEPT || $res['type'] == Follower::FOLLOW_PUBLIC) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return 0;
        }

    }

    public function getIsFollower()
    {
        $id = Yii::$app->user->identity->id;
        $modelFollower = new Follower();
        $res = $modelFollower->find()->where(['user_id' => $id, 'follower_id' => $this->id])->count();
        return (int) $res;

    }
    public function getGiftSummary()
    {

        $modelGiftHistory = new GiftHistory();

        $result = $modelGiftHistory->find()
            ->select(['count(id) as totalGift', 'sum(coin) as totalCoin'])
            ->where(['reciever_id' => $this->id, 'send_on_type' => GiftHistory::SEND_TO_TYPE_PROFILE])->asArray()->one();

        $totalGift = (int) $result['totalGift'];
        $totalCoin = (int) $result['totalCoin'];

        $response = [
            'totalGift' => $totalGift,
            'totalCoin' => $totalCoin

        ];
        return $response;

    }

  



    public function getPackage()
    {
        return $this->hasMany(Package::className(), ['id' => 'package_id']);
    }
    public function getFollowing()
    {
        
        return $this->hasMany(Follower::className(), ['follower_id' => 'id'])->where(['NOT',['type'=> Follower::FOLLOW_REQUEST]]);
        //->joinWith('follwingUser');

    }

    public function getBlockedUser()
    {
        return $this->hasMany(BlockedUser::className(), ['user_id' => 'id']);


    }

    public function getUserLiveDetail()
    {
        return $this->hasOne(UserLiveHistory::className(), ['user_id' => 'id'])->where(['user_live_history.status' => UserLiveHistory::STATUS_ONGOING]);
    }






    public function getFollower()
    {
        return $this->hasMany(Follower::className(), ['user_id' => 'id'])->where(['NOT',['type'=> Follower::FOLLOW_REQUEST]]);
    }
    public function getUserSetting()
    {
        return $this->hasMany(UserSetting::className(), ['user_id' => 'id']);
    }
    public function getTotalFollowing()
    {
        //return (int) $this->hasMany(Follower::className(), ['follower_id' => 'id'])->where(['NOT',['type'=> Follower::FOLLOW_REQUEST]])->count();
        return (int) $this->hasMany(Follower::className(), ['follower_id' => 'id'])->where(['NOT',['type'=> Follower::FOLLOW_REQUEST]])
        ->joinWith(['followingUserDetail'=> function ($query) {
            $query->select(['user.id','user.status']);
            $query->where(['user.status' => User::STATUS_ACTIVE]);
        }])
        ->count();
        
    }
    public function getTotalFollower()
    {
       // return (int) $this->hasMany(Follower::className(), ['user_id' => 'id'])->where(['NOT',['type'=> Follower::FOLLOW_REQUEST]])->count();
        return (int) $this->hasMany(Follower::className(), ['user_id' => 'id'])->where(['NOT',['type'=> Follower::FOLLOW_REQUEST]])
        ->joinWith(['followerUserDetail'=> function ($query) {
            $query->select(['user.id','user.status']);
            $query->where(['user.status' => User::STATUS_ACTIVE]);
        }])
        ->count();
    }



    /*public function getNotFollower()
    {
        return $this->hasMany(Follower::className(), ['user_id'=>'id'])->andOnCondition(['follower.follower_id'=>Yii::$app->user->identity->id]);
        //->andOnCondition(['reported_user.user_id' => Yii::$app->user->identity->id]);
        //
        //->joinWith('follwingUser');
        
    }*/
    public function getActiveSubscripton()
    {
        //getFullProfile
        return $this->hasOne(Subscription::className(), ['user_id' => 'id'])->where(['subscription.status' => Subscription::STATUS_ACTIVE])->andWhere(['>', 'subscription.expiry_date', time()]);
    }

    public function getTotalPost()
    {
        return (int) $this->hasMany(Post::className(), ['user_id' => 'id'])->where(['<>','post.status', Post::STATUS_DELETED])->count();
    }
    public function getTotalActivePost()
    {
        return (int) $this->hasMany(Post::className(), ['user_id' => 'id'])->where(['post.status' => Post::STATUS_ACTIVE])->count();
    }


    public function getTotalWinnerPost()
    {

        return (int) $this->hasMany(Post::className(), ['user_id' => 'id'])->where(['post.is_winning' => Post::IS_WINNING_YES])->count();
    }
    public function getTotalReel()
    {

        return (int) $this->hasMany(Post::className(), ['user_id' => 'id'])->where(['post.type' => Post::TYPE_REEL, 'status' => Post::STATUS_ACTIVE])->count();
    }
    public function getTotalClub()
    {
        return (int) $this->hasMany(Club::className(), ['user_id' => 'id'])->where(['status' => Club::STATUS_ACTIVE])->count();
    }

    public function getTotalMention()
    {
        return (int) $this->hasMany(MentionUser::className(), ['user_id' => 'id'])->count();
    }


    public function getisReported()
    {
        return $this->hasOne(ReportedUser::className(), ['report_to_user_id' => 'id'])->andOnCondition(['reported_user.user_id' => Yii::$app->user->identity->id]);
    }

    public function getUserPost()
    {
        return $this->hasMany(Post::className(), ['user_id' => 'id']);
    }


    public function getUserCompetition()
    {
        return $this->hasMany(CompetitionUser::className(), ['user_id' => 'id']);
    }

    public function getCompetitionWinnerUser()
    {
        return $this->hasMany(CompetitionPosition::className(), ['winner_user_id' => 'id']);
    }



    public function getInterest()
    {
        return $this->hasMany(UserInterest::className(), ['user_id' => 'id'])->select(['interest_id']);
        // ->joinWith('interestDetailName');
    }

    public function getLanguage()
    {
        return $this->hasMany(UserLanguage::className(), ['user_id' => 'id'])->select(['language_id']);
        // ->joinWith('languageName');
    }

    public function getProfileCategoriesName()
    {
        return $this->hasOne(ProfileCategoryType::className(), ['id' => 'profile_category_type'])->select(['name']);
        // ->joinWith('languageName');
    }


    public function getProfileInterest()
    {
        return $this->hasOne(UserInterest::className(), ['user_id' => 'id']);

    }

    public function getProfileLanguage()
    {
        return $this->hasOne(UserLanguage::className(), ['user_id' => 'id']);

    }

    public function getProfileSkip()
    {
        return $this->hasMany(DatingProfileViewAction::className(), ['profile_user_id' => 'id'])->andOnCondition(['dating_profile_view_action.user_id' => @Yii::$app->user->identity->id]);

    }

    public function getUserLikeByUser()
    {
        return (int) $this->hasOne(DatingProfileViewAction::className(), ['user_id' => 'id'])->where(['profile_user_id' => @Yii::$app->user->identity->id])->count();

    }

    public function getDatingMatchProfile()
    {
        $profileRelation = $this->hasMany(DatingMatchProfile::class, ['user_id' => 'id'])->joinWith(
            'profileViewAction'
        );

        if (($profileRelation->all() !== null)) {
            $checkMatch = $profileRelation->all();
            $userIds = array();
            foreach ($checkMatch as $matchData) {
                $userIds[] = @$matchData['profileViewAction']->profile_user_id;
            }
            if (in_array(@Yii::$app->user->identity->id, $userIds)) {
                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }

    }
    
    public function getNextUniueId()
    {
        $userModel =  User::find()->select(['max(unique_id) as last_unique_id'])->asArray()->one();
        $lastUniqueId =  (int)$userModel['last_unique_id'];
        if($lastUniqueId==0){
            $nextUniqueId = 100000;
        }else{
            $nextUniqueId = $lastUniqueId+1;
        }
        return $nextUniqueId;
    }
    public function getFeatureList()
    {
        $userId = Yii::$app->user->identity->id;
        $modelFeatureList =  new FeatureList();
        return $modelFeatureList->getListData(2,$userId);
       
    }

    public function getValidPhone($phone)
    {
        if($phone){
            $phoneNumber = str_replace(' ', '', $phone);
            $phoneNumber = ltrim($phoneNumber, "0");  
            return $phoneNumber;
        }
       
    }

    public function getValidPhoneCode($code)
    {
        if($code){
            $country_code = str_replace(' ', '', $code);
            $country_code = str_replace('+', '', $country_code);
            $country_code = ltrim($country_code, "0");  
            return $country_code;
        }
       
    }
    public function getPickleballSummary()
    {
        $model = new PickleballTeamPlayer();
        $result = $model->find()
            ->select(['count(pickleball_team_player.id) as totalMatch', 'sum(pickleball_team_player.point_gain) as totalPoint'])
            ->joinWith('pickleballMatchTeam')
            ->addSelect(["count(case when pickleball_match_team.winner_status='1' then 1 else null end) as totalWin"])
            ->addSelect(["count(case when pickleball_match_team.winner_status='2' then 1 else null end) as totalLoss"])
             
            ->joinWith('pickleballMatch')
            ->where(['pickleball_team_player.player_id' => $this->id,'pickleball_team_player.status' =>PickleballTeamPlayer::STATUS_ACTIVE])
            ->andWhere(['pickleball_match.status' =>PickleballMatch::STATUS_COMPLETED])
            ->groupBy('pickleball_team_player.player_id')
            ->asArray()->one();
        $totalMatch = (int) @$result['totalMatch'];
        $totalPoint = (int) @$result['totalPoint'];
        $totalWin = (int) @$result['totalWin'];
        $totalLoss = (int) @$result['totalLoss'];
        $response = [
            'totalMatch' => $totalMatch,
            'totalPoint' => $totalPoint,
            'totalWin' => $totalWin,
            'totalLoss' => $totalLoss
        ];
        return $response;

    }
    public function getPickleballTeamPlayer()
    {
        return $this->hasMany(PickleballTeamPlayer::className(), ['player_id' => 'id'])->andOnCondition(['pickleball_team_player.status' =>PickleballTeamPlayer::STATUS_ACTIVE]);

    }
    public function getSubscriptionPlanUser()
    {
        return $this->hasMany(SubscriptionPlanUser::className(), ['user_id' => 'id'])->select(['id','value','subscription_plan_id']);

    }
    public function getSubscribedStatus()
    {
        $subscribeStatus = 0;
        $result= $this->hasOne(SubscriptionPlanSubscriber::className(), ['subscribe_to_user_id' => 'id'])->andOnCondition(['subscription_plan_subscriber.subcriber_id' => @Yii::$app->user->identity->id])->one();
        if($result){ 
          
            if($result->expiry_time > time()){//active
                $subscribeStatus=1;    
            }else{ // expired
                $subscribeStatus=2;
            }           
        }
        return $subscribeStatus;

    }
    public function getIsSubscriptionAllowed()
    {
        
        $modelSetting = new Setting();

        $settingResult = $modelSetting->find()->one();
        $minimumFollowerRequired = (int) $settingResult->subscribe_active_condition_follower;
        $minimumPostRequired = (int) $settingResult->subscribe_active_condition_post;
        $isAllowed=0;
        if($this->totalFollower >= $minimumFollowerRequired && $this->totalActivePost>= $minimumPostRequired){
            $isAllowed=1;
        }
        return $isAllowed;

    }
    
    
    
    
    
   
   

}