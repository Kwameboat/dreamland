<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;



/**
 * This is the model class 
 *
 */
class JobApplication extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;
    const STATUS_PENDING =1;
    const STATUS_ACCEPTED=2;
    const STATUS_REJECTED=3;

    const COMMON_NO=0;
    const COMMON_YES=1;
    
    
    public $imageFile;

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'job_applications';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            
            [['job_id', 'total_experience','education','resume'], 'required'],
            [['status', 'job_id','total_experience','created_by','created_at','updated_at','updated_by'], 'integer'],
            [['resume','cover_letter','education'], 'string'],
            [['job_id', 'total_experience','education','resume'], 'required','on'=>['create','update']],
            

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'job_id' => Yii::t('app', 'Job'),
            'status' => Yii::t('app', 'Status'),
            'total_experience' => Yii::t('app', 'Total experience'),
            'resume' => Yii::t('app', 'Resume'),
            'cover_letter' => Yii::t('app', 'Cover Letter'),
            'education' => Yii::t('app', 'Education'),
            
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



    public function getStatusDropDownData()
    {
        return array(self::STATUS_PENDING=>'Pending',self::STATUS_ACCEPTED=>'Accept',self::STATUS_REJECTED=>'Reject',self::STATUS_ACTIVE => 'Active', self::STATUS_INACTIVE => 'Inactive');
    }

    public function getStatus()
    {
       if($this->status==$this::STATUS_INACTIVE){
           return 'Inactive';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }else if($this->status==$this::STATUS_PENDING){
        return 'Pending';    
    }else if($this->status==$this::STATUS_ACCEPTED){
        return 'Accept';    
    }else if($this->status==$this::STATUS_REJECTED){
        return 'Reject';    
    }
    }
  
    
    
    public function getResumeUrl()
    {
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_JOB_APPLICATION,$this->resume);

        
    }

    
    public function getUserDetails()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);

    }
    
    public function getJob()
    {
        return $this->hasOne(Job::className(), ['id' => 'job_id']);

    }
}
