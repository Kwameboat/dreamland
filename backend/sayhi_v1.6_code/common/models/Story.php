<?php
namespace common\models;

use Yii;
use yii\helpers\ArrayHelper;
use common\models\ReportedStory;

class Story extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;
    const STATUS_BLOCKED=9;
    const TYPE_TEXT =1;
    const TYPE_IMAGE =2;
    const TYPE_VIDEO =3;

    const STORY_TYPE_ACTIVE =1;
    const STORY_TYPE_COMPLETE =2;
    
    public $audioFile;
    public $imageFile;
    public $filter_id;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'story';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            
            [['status', 'id','user_id','created_at','type'], 'integer'],
            [['image','description','video','background_color'], 'string'],
            [['description'], 'string', 'max' => 200],
            // [['type'], 'required','on'=>['create','update']],
            // [['stories'], 'required','on'=>'createMain'],
            // [['stories'], 'save'],
            [['filter_id'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => Yii::t('app', 'User'),
            'description' => Yii::t('app', 'Description'),
            'status' => Yii::t('app', 'Status'),
            'video' => Yii::t('app', 'Video'),
            'background_color' => Yii::t('app', 'Background color'),
            'type' => Yii::t('app', 'Type'),
            'image' => Yii::t('app', 'Thumbnail'),
            'created_at' => Yii::t('app', 'Created'),
            'filter_id' => Yii::t('app', 'Status'),
           
        ];
    }
   
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            // $this->created_by =   Yii::$app->user->identity->id;
          
        }else{
            // $this->updated_at = time();
            // $this->updated_by =   Yii::$app->user->identity->id;

        }

        
        return parent::beforeSave($insert);
    }
    

    public function getStatus()
    {
       if($this->status==$this::STATUS_INACTIVE){
           return 'Inactive';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }
    }
    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_INACTIVE => 'Inactive');
    }

    public function getVideoUrl(){
        if($this->video){
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_STORY,$this->video);
         
        }
     }

    public function getImageUrl(){
        
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_STORY,$this->image);
        
    }


    public function getUser()
    {
        return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }
   


    public function getType()
    {
       if($this->type==$this::TYPE_TEXT){
           return 'Text';
       }else if($this->type==$this::TYPE_IMAGE){
           return 'Image';    
       }else if($this->type==$this::TYPE_VIDEO){
        return 'Video';    
    }
    }

    public function getFilterStatus()
    {
        if($this->status==$this::STATUS_INACTIVE){
            return 'Inactive';
        }else if($this->status==$this::STATUS_ACTIVE){

            $conditionTime = strtotime('-24 hours', time());   
            if(@$this->created_at > $conditionTime ){
                 return 'Active'; 
            }else{
                return 'Expired'; 
            }
        }
       
    }

    public function getFilter()
    {
        return array($this::STORY_TYPE_ACTIVE => 'Active', $this::STORY_TYPE_COMPLETE => 'Expired');
    }

    public function getStoryTotalCount(){
        return Story::find()->where(['<>','status',Story::STATUS_DELETED])->count();
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

    public function getLastTweleveMonthStory()
    {
        
        $totalAds = [];
        $monthArr =[];
        $months = $this->getLastTweleveMonth();
        $res= Yii::$app->db->createCommand("SELECT month(from_unixtime(created_at)) as month, count(id) as total_ad FROM story where status=10 and from_unixtime(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) group by month")->queryAll();
        foreach($months as $key => $month){
            $found_key = array_search($key, array_column($res, 'month'));  
            $totalAd =0;
            if(is_int($found_key)){
                if($res[$found_key]['total_ad']){
                    $totalAd =   round($res[$found_key]['total_ad']);
                }
            }
            $totalAds[]=$totalAd;
            $monthArr[]=$month;

        }
        $output=[];

        $output['data'] = $totalAds;
        $output['dataCaption'] = $monthArr;
        return $output;

        
    }

    public function getReportedStoryActive()
    {
        return $this->hasMany(ReportedStory::className(), ['story_id'=>'id'])->andOnCondition(['reported_story.status' => ReportedStory::STATUS_PENDING]);
        
    }

    public function getReportedStory()
    {
        return $this->hasMany(ReportedStory::className(), ['story_id'=>'id']);
        
    }

}
