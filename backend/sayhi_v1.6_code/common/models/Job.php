<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use common\models\GiftCategory;


/**
 * This is the model class 
 *
 */
class Job extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    const COMMON_NO=0;
    const COMMON_YES=1;

    const JOB_TYPE_DAYSHIFT = 1;
    const JOB_TYPE_NIGHTSHIFT = 2;
    
    
    public $imageFile;

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'jobs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['organization_id', 'title','skill','description','type','experience_min','experience_max','salary_min','salary_max'], 'required'],
            [['status', 'id','category_id','organization_id','type','country_id','state_id','city_id','experience_min','experience_max','salary_min','salary_max','created_by','created_at','updated_by','updated_at'], 'integer'],
            [['title','description','skill','education',], 'string'],
            [['organization_id', 'title','skill','description','type','experience_min','experience_max','salary_min','salary_max'], 'required','on'=>['create','update']],
            

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'organization_id' => Yii::t('app', 'Organization'),
            'status' => Yii::t('app', 'Status'),
            'category_id' => Yii::t('app', 'Category'),
            'title' => Yii::t('app', 'Job Title'),
            'description' => Yii::t('app', 'Description'),
            'skill' => Yii::t('app', 'Skill'),
            'type' => Yii::t('app', 'Job Type'),
            'experience_min' => Yii::t('app', 'Minimum Year Experience'),
            'experience_max' => Yii::t('app', 'Maximum Year Experience'),
            'salary_min' => Yii::t('app', 'Salary Minimum'),
            'salary_max' => Yii::t('app', 'Salary Maximum'),
            'state_id' => Yii::t('app', 'State'),
            'country_id' => Yii::t('app', 'Country'),
            'city_id' => Yii::t('app', 'City'),
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

    public function getPaidDropDownData()
    {
        return array(self::COMMON_NO => 'No', self::COMMON_YES => 'Yes');
    }


    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_INACTIVE => 'Inactive');
    }

    public function getStatus()
    {
       if($this->status==$this::STATUS_INACTIVE){
           return 'Inactive';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }
    }
  
    
    
    public function getImageUrl()
    {
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_GIFT,$this->image);

        
    }

    
    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['id' => 'category_id']);

    }

   
    public function getJobTypeDropDown()
    {
        return array(self::JOB_TYPE_DAYSHIFT => 'DayShift', self::JOB_TYPE_NIGHTSHIFT => 'NightShift');
    }

    public function getJobType()
    {
       if($this->type==$this::JOB_TYPE_DAYSHIFT){
           return 'DayShift';
       }else if($this->type==$this::JOB_TYPE_NIGHTSHIFT){
           return 'NightShift';    
       }
    }
    
    public function getOrganization()
    {
        return $this->hasOne(Organization::className(), ['id' => 'organization_id']);

    }

}
