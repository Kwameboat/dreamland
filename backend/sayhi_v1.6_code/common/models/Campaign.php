<?php
namespace common\models;
//use common\models\User;
use common\models\CampaignExampleImage;
use common\models\Category;
use common\models\organization;
use common\models\Setting;
use Yii;

use api\modules\v1\models\User;

class Campaign extends \yii\db\ActiveRecord
{
    
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 10;
    const STATUS_INACTIVE = 9;
    // const STATUS_COMPLETED = 10;

    const COMPETITION_MEDIA_TYPE_IMAGE = 1;
    const COMPETITION_MEDIA_TYPE_VIDEO = 2;
    const COMPETITION_MEDIA_TYPE_AUDIO = 3;

    public $imageFile;
    public $exampleFile;
    public $deletePhoto;

    public $competition_id;


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'campaign';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['campaigner_id', 'id','campaign_for_id','status','created_at','created_by','updated_at','updated_by','category_id' ], 'integer'],
           
            [['title', 'description'], 'string'],
            [['target_value', 'raised_value' ], 'number'],
            [['start_date','end_date'], 'safe'],
            [['title', 'cover_image'], 'string', 'max' => 100],
            
             [['exampleFile','target_value', 'raised_value','imageFile','category_id'], 'required','on'=>'create'],
            [['imageFile'], 'file', 'skipOnEmpty' => true],
            [['exampleFile'], 'file', 'skipOnEmpty' => true, 'maxFiles' => 100],
            [['deletePhoto','start_date', 'end_date'], 'safe'],
            [['end_date'], 'checkEndDate', 'on' => ['create']],
           
         
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => Yii::t('app', 'Title'),
            'description' => Yii::t('app', 'Description (optional)'),
            'start_date' => Yii::t('app', 'Start date'),
            'end_date' => Yii::t('app', 'End date'),
            'campaigner_id' => Yii::t('app', 'Campaigner '),
            'campaign_for_id' => Yii::t('app', 'Campaigner For '),
            'target_value' => Yii::t('app', 'Target Value'),
            'raised_value' => Yii::t('app', 'Raised Value'),
            'status' => Yii::t('app', 'Status'),
            'cover_image' => Yii::t('app', 'Cover image'),
            'imageFile' => Yii::t('app', 'Cover image'),
            'exampleFile' => Yii::t('app', 'Images'),
            

        
            
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
    public function checkEndDate($attribute, $params, $validator)
    {
        if(!$this->hasErrors()){
            if($this->start_date > $this->end_date ){
                $this->addError($attribute, Yii::t('app','End date must be greater than start date'));  
            }
        
            
        }
       
    }

    
    public function getStatusString()
    {
       if($this->status==$this::STATUS_INACTIVE){
           return 'Inactive';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }else if($this->status==$this::STATUS_DELETED){
         return 'Delete';    
       }
    }

    


    public function getStatusButton()
    {
        if($this->status==$this::STATUS_INACTIVE){
            return 'Inactive';
        }else if($this->status==$this::STATUS_ACTIVE){
            return 'Active';    
        }
        else if($this->status==$this::STATUS_DELETED){
            return 'Delete';    
        }
    }


    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_INACTIVE => 'Inactive');
    
    }



    public function getImageUrl()
    {
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_CAMPAGIN,$this->cover_image);

        
    }
   
    public function getExpampleImages()
    {
        return $this->hasMany(CampaignExampleImage::className(), ['campaign_id' => 'id']);

    }


    public function getorgnization()
    {
        return $this->hasOne(organization::className(), ['id' => 'campaign_id']);

    }

      
   
    public function getCompetitionCount()
    {
        return Competition::find()->where(['<>','status',self::STATUS_DELETED])->count();
    }

    public function getCompetitionMediaTypeData()
    {
        $modelSetting = new Setting();
        $resSetting = $modelSetting->getSettingData();
        $mediaType =  [];
        if($resSetting->is_upload_image){
            $mediaType[1] = 'Image';
        }
        if($resSetting->is_upload_video){
            $mediaType[2] = 'Video';
        }
        return $mediaType;
    }


    public function getCompetitionUser()
    {
        return $this->hasMany(CompetitionUser::className(), ['competition_id' => 'id']);

    }


    public function getPost()
    {
        return $this->hasMany(Post::className(), ['competition_id' => 'id']);

    }

  
  



}
