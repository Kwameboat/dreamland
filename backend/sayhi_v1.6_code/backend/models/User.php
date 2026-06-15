<?php
namespace app\models;
use common\models\Country;
use common\models\ReportedUser;
use yii\web\ForbiddenHttpException;
use common\models\Follower;
use common\models\BlockedUser;
use common\models\Story;
use common\models\UserLoginLog;
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
 * @property string $update_coin;
 */
class User extends \yii\db\ActiveRecord
{
    
    const ROLE_ADMIN = 1;
    const ROLE_SUBADMIN=2;
    const ROLE_CUSTOMER=3;
    const ROLE_AGENT =4;


    const STATUS_DELETED = 0;
    const STATUS_PENDING=1;
    const STATUS_REJECTED=2;
    
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;

    const IS_VERIFIED_NO = 0;
    const IS_VERIFIED_YES = 1;
    

    public $password;
    public $confirmPassword;
    public $imageFile;
    public $update_coin;
   
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            
            [[ 'email','username'], 'required'],
            [['status', 'created_at', 'updated_at','country_id','is_verified','country_id','update_coin'], 'integer'],
            [['username','name', 'password_hash', 'password_reset_token', 'email', 'verification_token','address','phone','city','postcode','website'], 'string', 'max' => 255],
            [['auth_key'], 'string', 'max' => 32],
            [['username'], 'checkUniqueUsername'],
            [['email'], 'checkUniqueEmail'],
            [['password_reset_token'], 'unique'],
            [['password','confirmPassword'], 'required','on'=>'create'],
            [['password'], 'string', 'min' => 6],
            [['confirmPassword'], 'compare', 'compareAttribute' => 'password'],
            [['imageFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg'],
            [['update_coin'], 'required', 'on' => 'updateCoin'],
            [['update_coin'], 'checkUpdateCoin','on' => 'updateCoin'],
            [['update_coin'], 'save'],
             
            [['password', 'confirmPassword'], 'required', 'on' => 'resetPassword']
            
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
            'country_id' => 'Country',
            'is_verified' => 'Is verified?'
            
            
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert) {

            $this->created_at = time();
            $this->password_hash = Yii::$app->security->generatePasswordHash($this->password);
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
    public function checkUpdateCoin($attribute, $params, $validator)
    {
      
        if(!$this->hasErrors()){
            if($this->$attribute==0){
                $this->addError($attribute, 'Coin can not be zero');     
            }
            
        }
       
    }
    public function getLastTweleveMonth()
    {
        $month =  strtotime("+1 month");
        for ($i = 1; $i <= 12; $i++) {
            $months[(int)date("m", $month)] = date("M", $month);
            $month = strtotime('+1 month', $month);
        }
        return $months;
        
    }

    
    public function getIsVerifiedString()
    {
        if($this->is_verified==$this::IS_VERIFIED_YES){
           return 'Yes';    
        }else {
            return 'No';    
        }
       
    }


    public function getLastTweleveMonthUser()
    {
        
        $totalAds = [];
        $monthArr =[];
        $months = $this->getLastTweleveMonth();

        
        
        $res= Yii::$app->db->createCommand("SELECT month(from_unixtime(created_at)) as month, count(id) as total_ad FROM user where role=3 and status!=0 and from_unixtime(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) group by month")->queryAll();

        foreach($months as $key => $month){
            $found_key = array_search($key, array_column($res, 'month'));  
            //echo gettype($found_key), "\n";
            if(is_int($found_key)){
                $totalAd =  $res[$found_key]['total_ad'];
            }else{
                $totalAd = 0;
            }
            //echo $totalAds;
            /*echo '=====================';
            echo '<br>';
            echo $key.'#'.$month;
            echo '<br>';*/

            //print_r($found_key);
            
            $totalAds[]=$totalAd;
           
            $monthArr[]=$month;

        }
        $output=[];

        $output['data'] = $totalAds;
        $output['dataCaption'] = $monthArr;
        return $output;

        
    }
    



    public function getSex()
    {
       if($this->sex==1){
           return 'Male';
       }else if($this->sex==2){
           return 'Female';    
       }else{
           return 'Other';
       }
    }
    
    public function checkPageAccess()
    {
        
        if(Yii::$app->user->identity->role==User::ROLE_SUBADMIN && Yii::$app->params['siteMode']==3  ){
        
            throw new ForbiddenHttpException('You are not allowed to take this action in demo');
        }
    }
    
    public function getStatus()
    {
       if($this->status==$this::STATUS_INACTIVE){
           return 'Inactive';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }else if($this->status==$this::STATUS_PENDING){
            return 'Approval Pending';    
        }else if($this->status==$this::STATUS_REJECTED){
            return 'Rejected';    
        }
    }
    public function getStatusButton()
    {
        if($this->status==$this::STATUS_INACTIVE){
            return'<button type="button" class="btn btn-sm expired_btn">'.Yii::t('app','Inactive').'</button>'; 
        }else if($this->status==$this::STATUS_ACTIVE){
            return'<button type="button" class="btn btn-sm active_btn">'.Yii::t('app','Active').'</button>';      
        }else if($this->status==$this::STATUS_PENDING){
            return'<button type="button" title="Approval Pending" class="btn btn-sm pending_btn">'.Yii::t('app','Pending').'</button>';  
            
         }else if($this->status==$this::STATUS_REJECTED){
            return'<button type="button" class="btn btn-sm expired_btn">'.Yii::t('app','Rejected').'</button>'; 
         }
        


       
    }
    public function getRole()
    {
       if($this->role==$this::ROLE_CUSTOMER){
           return 'User';
       }else if($this->role==$this::ROLE_AGENT){
           return 'Agent';    
       }
    }

    public function getEmail()
    {
        
        if(Yii::$app->user->identity->role==User::ROLE_SUBADMIN && Yii::$app->params['siteMode']==3  ){
            
           
            $maskedEmail = '';
            if($this->email){
                $email = $this->email;
                $startingChars = substr($email, 0, 2);
                $endingChars = substr($email, -5);
                $maskedEmail = $startingChars.'**********'. $endingChars;
            }
        
            return $maskedEmail;
        }else{
           return  $this->email;
        }
       
    }
    public function getPhone()
    {
        
        if(Yii::$app->user->identity->role==User::ROLE_SUBADMIN && Yii::$app->params['siteMode']==3  ){
            $masked = '';
            if($this->phone){
                $endingChars = substr($this->phone, -4);
                $masked = '**********'. $endingChars;
            }
            return $masked;
        }else{
            return $this->country_code.' '.$this->phone;
        }
       
    }
    
    
    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_INACTIVE => 'Inactive',self::STATUS_PENDING => 'Approval Pending',self::STATUS_REJECTED => 'Rejected');
    }

    public function getVerifiedStatusDropDownData()
    {
        return array(self::IS_VERIFIED_NO => 'No', self::IS_VERIFIED_YES => 'Yes');
    }

    public function getVerifiedStatus()
    {
       if($this->is_verified==$this::IS_VERIFIED_NO){
           return 'No';
       }else if($this->is_verified==$this::IS_VERIFIED_YES){
           return 'Yes';    
       }
    }

    public function getPhoneVerifiedStatus()
    {
       if($this->is_phone_verified==$this::IS_VERIFIED_NO){
           return 'No';
       }else if($this->is_phone_verified==$this::IS_VERIFIED_YES){
           return 'Yes';    
       }
    }
    public function getEmailVerifiedStatus()
    {
       if($this->is_email_verified==$this::IS_VERIFIED_NO){
           return 'No';
       }else if($this->is_email_verified==$this::IS_VERIFIED_YES){
           return 'Yes';    
       }
    }
    public static function findByUsernameEmail($username)
    {

        return static::find()->where(['status' => self::STATUS_ACTIVE])
            ->andWhere(['or', ['username' => $username], ['email' => $username]])->one();

    }


    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }


    public function getCountry()
    {
        return $this->hasOne(Country::className(), ['id'=>'country_id']);
        
    }

    public function getCountryDetail()
    {
        return $this->hasOne(Country::className(), ['id'=>'country_id']);
        
        
    }


    public function getUserCount()
    {
        return $this->find()->where(['role' => self::ROLE_CUSTOMER])->andWhere(['<>','status', self::STATUS_DELETED])->count();
    }

    

    public function getLatestUsers()
    {
        return  $this->find()->where(['role' => self::ROLE_CUSTOMER])->andWhere(['<>','status', self::STATUS_DELETED])->orderBy(['id'=>SORT_DESC])->limit(8)->all();

    }

    public function getImageUrl(){
        
        $image = $this->image;
        if(empty($this->image)){
            $image  ='default.png';
        }
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_USER,$image);

        //return Yii::$app->params['pathUploadUser'].'/'.$image;
        
        
    }
    
    public function getReportedUser()
    {
        return $this->hasMany(ReportedUser::className(), ['report_to_user_id'=>'id'])->orderBy(['id' => SORT_DESC]);
        
    }
    
    public function getReportedUserActive()
    {
        return $this->hasMany(ReportedUser::className(), ['report_to_user_id'=>'id'])->andOnCondition(['reported_user.status' => ReportedUser::STATUS_PENDING]);
        
    }

    function getTotalFollowers($userId){
        $result = Follower::Find()->where(['user_id'=>$userId])->andWhere(['NOT',['type'=> Follower::FOLLOW_REQUEST]])->count();
        return $result;
    }

    function getTotalFollowing($userId){
        $result = Follower::Find()->where(['follower_id'=>$userId])->andWhere(['NOT',['type'=> Follower::FOLLOW_REQUEST]])->count();
        return $result;
    }

    function getTotalBlockedUsers($userId){
        $result = BlockedUser::Find()->where(['user_id'=>$userId])->count();
        return $result;
    }
    
    function getTotalStory($userId){
        $result = Story::Find()->where(['user_id'=>$userId])->count();
        return $result;
    }
    function getLastLoginLog(){
        $result = UserLoginLog::Find()->where(['user_id'=>$this->id])->orderBy(['user_login_log.id' => SORT_DESC])->one();
        return $result;
    }

}
