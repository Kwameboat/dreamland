<?php
namespace backend\models;
use Yii;

use common\models\Category;
use common\models\Country;
use common\models\State;
use common\models\City;
use common\models\AdImage;
use app\models\User;
use common\models\ReportedAd;
use common\models\MessageGroup;
use common\models\PromotionalBanner;

class Ad extends \yii\db\ActiveRecord
{
    
    const STATUS_ACTIVE=10;
    
    const STATUS_DELETED = 0;
    const STATUS_PENDING = 1;
    const STATUS_REJECTED = 2;
    const STATUS_EXPIRED = 3;
    const STATUS_SOLD = 4;


    const FEATURED_NO   = 0;
    const FEATURED_YES  =   1;

    const IS_BANNER_AD_NO   = 0;
    const IS_BANNER_AD_YES  = 1;
    
   
  //  public $imageFile;
    public $featured_amount;

    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ad';
    }

    

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[ 'description', 'negotiable', 'hide_phone', 'location'], 'string'],
            [['status','user_id', 'category_id', 'sub_category_id', 'price', 'view', 'start_date', 'expire_date', 'admin_seen','featured', 'created_by', 'updated_by','package_banner_id'], 'integer'],
            [['created_at', 'updated_at','featured_amount','featured_exp_date'], 'safe'],
            [['title', 'longitude'], 'string', 'max' => 256],
            [['featured_amount'], 'number'],
            [['phone'], 'string', 'max' => 50],
            [['latitude'], 'string', 'max' => 255],
            

            [[ 'title','status','category_id' ], 'required','on'=>'update'],
            [[ 'featured_exp_date'], 'required','on'=>'makeFeatured']

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
            'category_id' => Yii::t('app','Category'),
            'sub_category_id' => Yii::t('app','Sub Category'),
            'title' => Yii::t('app','Title'),
            'description' => Yii::t('app','Description'),
            'price' => Yii::t('app','Price'),
            'featured' => Yii::t('app','Featured'),
            'negotiable' => Yii::t('app','Negotiable'),
            'phone' => Yii::t('app','Phone'),
            'hide_phone' => Yii::t('app','Hide Phone'),
            'location' => Yii::t('app','Location'),
            'latitude' => Yii::t('app','Latitude'),
            'longitude' => Yii::t('app','Longitude'),
            'view' => Yii::t('app','Views'),
            'start_date' => Yii::t('app','Start Date'),
            'expire_date' => Yii::t('app','Expire Date'),
            'featured_exp_date' => Yii::t('app','Featured Expiry Date'),
            'admin_seen' => Yii::t('app','Admin Seen'),
            'status' => Yii::t('app','Status'),
            'featured_amount' => Yii::t('app','Received Amount'),

            
            'created_at' => Yii::t('app','Created At'),
            'created_by' => Yii::t('app','Created By'),
            'updated_at' => Yii::t('app','Updated At'),
            'updated_by' => Yii::t('app','Updated By'),
            'package_banner_id' => Yii::t('app','Banner Package'),

        ];
    }

    
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->created_by =   Yii::$app->user->identity->id;
          
        }else{

           
            $this->updated_at = time();
            $this->updated_by =   Yii::$app->user->identity->id;

        }
        return parent::beforeSave($insert);
    }

   
    public function getStatusString()
    {
        if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
        }else if($this->status==$this::STATUS_DELETED){
            return 'Deleted';    
        }else if($this->status==$this::STATUS_PENDING){
            return 'Pending';    
        }else if($this->status==$this::STATUS_REJECTED){
            return 'Rejected';    
        }else if($this->status==$this::STATUS_EXPIRED){
            return 'Expired';    
        }else if($this->status==$this::STATUS_SOLD){
            return 'Sold';    
        }
       
    }

    

    public function getHidePhoneString()
    {
       if($this->hide_phone==0){
           return 'No';
       }else if($this->hide_phone==1){
           return 'Yes';    
       }
    }

    public function getNegotiableString()
    {
       if($this->negotiable==0){
           return 'No';
       }else if($this->negotiable==1){
           return 'Yes';    
       }
    }
    public function getFeaturedString()
    {
       if($this->featured==0){
           return 'No';
       }else if($this->featured==1){
           return 'Yes';    
       }
    }

    public function getIsBannerAdString()
    {
       if($this->is_banner_ad==0){
           return 'No';
       }else if($this->is_banner_ad==1){
           return 'Yes';    
       }
    }
  
    public function getIsDealString()
    {
       if($this->isDeal==1){
           return 'Yes';
       }else{
           return 'No';    
       }
    }
    
    public function getIsDeal()
    {
        return ($this->deal_start_date < time() && $this->deal_end_date >= time())? 1: 0;
        
    }

    public function getDealDate()
    {
       
        return  Yii::$app->formatter->asDate($this->deal_start_date).' To '.Yii::$app->formatter->asDate($this->deal_end_date);
        
        
    }
    
    
    

    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_PENDING => 'Pending',self::STATUS_REJECTED=>'Rejected',self::STATUS_EXPIRED=>'Expired',self::STATUS_SOLD=>'Sold');
    }

    public function getHidePhoneDropDownData()
    {
        return array(0 => 'No', 1 => 'Yes');
    }

    public function getActiveJobCount()
    {
       return $this->find()->where(['status'=>self::STATUS_ACTIVE])->count();
    }

    public function getPendingJobCount()
    {
       return $this->find()->where(['status'=>self::STATUS_PENDING])->count();
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


    public function getLastTweleveMonthAds()
    {
        
        $totalAds = [];
        $monthArr =[];
        $months = $this->getLastTweleveMonth();
        
        $res= Yii::$app->db->createCommand("SELECT month(from_unixtime(created_at)) as month, count(id) as total_ad FROM ad where from_unixtime(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) group by month")->queryAll();

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
    

  
    /**
     * RELEATION START
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }
    /*
     public function getCountryDetail()
    {
        return $this->hasOne(Country::className(), ['id'=>'country_id']);
        
    }
    public function getStateDetail()
    {
        return $this->hasOne(State::className(), ['id'=>'state_id']);
        
    }
    
     public function getCityDetail()
    {
        return $this->hasOne(City::className(), ['id'=>'city_id']);
        
    }
*/
    public function getMainCategory()
    {
        return $this->hasOne(Category::className(), ['id'=>'category_id'])->andOnCondition(['type'=>Category::TYPE_AD_CATEGORY]);
        
    }

    public function getSubCategory()
    {
        return $this->hasOne(Category::className(), ['id'=>'sub_category_id']);
        
    }

    public function getAdImage()
    {
        return $this->hasMany(AdImage::className(), ['ad_id'=>'id'])->andOnCondition(['status' => AdImage::STATUS_ACTIVE]);
        
    }

    public function getReportedAd()
    {
        return $this->hasMany(ReportedAd::className(), ['ad_id'=>'id']);
        
    }
    
    public function getReportedAdActive()
    {
        return $this->hasMany(ReportedAd::className(), ['ad_id'=>'id'])->andOnCondition(['reported_ad.status' => ReportedAd::STATUS_PENDING]);
        
    }
    public function getMessageGroup()
    {
       
       
       return $this->hasOne(MessageGroup::className(), ['ad_id'=>'id'])->andOnCondition(['sender_id' => Yii::$app->user->identity->id]);
        
        
    }

     
    public function getMainImage($size='s')
    {
        $images = $this->adImage;
        $image  ='default.png';
        if(count($images)){
            $image = $images[0]->image;
        }
        if($size=='L'){
            return Yii::$app->urlManagerFrontend->baseUrl.'/uploads/ad/original/'.$image;
        }elseif($size=='M'){
            return Yii::$app->urlManagerFrontend->baseUrl.'/uploads/ad/medium/'.$image;
        }else{
            return Yii::$app->urlManagerFrontend->baseUrl.'/uploads/ad/thumb/'.$image;
        }
        
        //return $this->hasMany(ReportedAd::className(), ['ad_id'=>'id'])->andOnCondition(['reported_ad.status' => ReportedAd::STATUS_PENDING]);
        
    }

    public function getBannerPackage()
    {
        return $this->hasOne(PromotionalBanner::className(), ['id'=>'package_banner_id']);
        
    }


    
}
