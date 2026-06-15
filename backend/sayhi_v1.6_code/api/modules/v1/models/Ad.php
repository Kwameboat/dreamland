<?php
namespace api\modules\v1\models;
use Yii;

//use api\modules\v1\Category;
//use api\modules\v1\Country;
//use api\modules\v1\State;
//use common\models\City;
//use api\modules\v1\AdImage;
//use api\modules\v1\User;
use api\modules\v1\models\AdPackage;
use api\modules\v1\models\UserLocation;
use api\modules\v1\models\FavoriteAd;
use api\modules\v1\models\ReportedAd;


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

    public $adId;
    public $is_follower;
   
  //  public $imageFile;

    
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
            [[ 'description', 'featured', 'negotiable', 'hide_phone', 'location','price','currency'], 'string'],
            [['status','is_banner_ad','package_banner_id','user_id', 'category_id', 'sub_category_id',   'view', 'start_date', 'expire_date', 'featured_exp_date', 'admin_seen', 'deal_start_date','deal_end_date','created_at','created_by', 'updated_by'], 'integer'],
            [['deal_price'], 'number'],
            [['created_at', 'updated_at','is_follower'], 'safe'],
            [['title', 'longitude'], 'string', 'max' => 256],
            [['phone'], 'string', 'max' => 50],
            [['latitude'], 'string', 'max' => 255],
            ['status', 'in', 'range' => [0,1, 2, 3,4,10]],

            [[ 'title','category_id','currency' ], 'required','on'=>'create'],
            [[ 'title','category_id','currency' ], 'required','on'=>'update'],
            [[ 'status','adId' ], 'required','on'=>'updateStatus'],
            [[ 'adId' ], 'required','on'=>'reportAd'],
            

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
            'featured_exp_date' => Yii::t('app','Featured Exp Date'),
            'admin_seen' => Yii::t('app','Admin Seen'),
            'status' => Yii::t('app','Status'),
            'created_at' => Yii::t('app','Created At'),
            'created_by' => Yii::t('app','Created By'),
            'updated_at' => Yii::t('app','Updated At'),
            'updated_by' => Yii::t('app','Updated By')
        ];
    }

    
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->created_by  =   Yii::$app->user->identity->id;
            $this->user_id       =   Yii::$app->user->identity->id;
          
        }else{

           
            $this->updated_at = time();
            $this->updated_by =   Yii::$app->user->identity->id;

        }
        return parent::beforeSave($insert);
    }

    public function fields()
    {
        $fields = parent::fields();

        
       //$fields[] = 'statusString';
       $fields['status_text'] = (function($model){
        return $model->statusString;
        });
       $fields['user'] = (function($model){
            return @$model->user->name;
       });
        $fields['cateogry_name'] = (function($model){
            return @$model->mainCategory->name;
        });

        $fields['sub_cateogry_name'] = (function($model){
            return @$model->subCategory->name;
        });

        $fields['is_favorite'] = (function($model){
            return (@$model->isFavorite) ? 1: 0;
        });

        $fields['is_reported'] = (function($model){
            
            return (@$model->isReported) ? 1: 0;
        });

        $fields['is_deal'] = (function($model){
            return ($model->deal_start_date < time() && $model->deal_end_date >= time())? 1: 0;

        });

       
        $fields['images'] = (function($model){
            $imageArr=[];
            
            // return 
            foreach($model->imagesAd as $img){
                $imageArr[]= Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_AD_IMAGES,$img->image);
                //  Yii::$app->params['siteUrl'].Yii::$app->urlManagerFrontend->baseUrl.'/uploads/ad/medium/'.$img->image;

            }

            return $imageArr; 
        });

        $fields[] = "locations";

       
      //  $fields[] = 'images';
       

        return $fields;
    }

    public function extraFields()
    {
        return ['user','images'];
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
    

    
    
    public function getActiveJobCount()
    {
       return $this->find()->where(['status'=>self::STATUS_ACTIVE])->count();
    }

    public function getPendingJobCount()
    {
       return $this->find()->where(['status'=>self::STATUS_PENDING])->count();
    }

    public function getUserActiveJob($userId)
    {
       return $this->find()->where(['status'=>self::STATUS_ACTIVE,'user_id'=>$userId])->count();
    }



    public function getAdExpiry()
    {
        $packageId =  Yii::$app->user->identity->ad_package_id;
        $modelPackage =  new AdPackage();
        $package  = $modelPackage->findOne($packageId);
        $adDuration = $package->ad_duration;
        return  strtotime("+$adDuration days", time());
    
    }
    
    public function getAdFeaturedExpiry()
    {
        $packageId =  Yii::$app->user->identity->ad_package_id;
        $modelPackage =  new AdPackage();
        $package  = $modelPackage->findOne($packageId);
        $adDuration = $package->featured_duration;
        return  strtotime("+$adDuration days", time());
    
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
        return $this->hasOne(Category::className(), ['id'=>'category_id']);
        
    }

    public function getSubCategory()
    {
        return $this->hasOne(Category::className(), ['id'=>'sub_category_id']);
        
    }

    public function getImagesAd()
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
    public function getLocations()
    {
        return $this->hasOne(UserLocation::className(), ['ad_id'=>'id'])->andOnCondition(['user_location.type' => UserLocation::TYPE_AD,'user_location.status' => UserLocation::STATUS_ACTIVE]);
        
    }
    public function getFavorite()
    {
        return $this->hasMany(FavoriteAd::className(), ['ad_id'=>'id']);
        
    }

    public function getIsFavorite()
    {
        return $this->hasOne(FavoriteAd::className(), ['ad_id'=>'id'])->andOnCondition(['favorite_ad.user_id' => Yii::$app->user->identity->id]);
        
    }
    
    public function getisReported()
    {
        
        return $this->hasOne(ReportedAd::className(), ['ad_id'=>'id'])->andOnCondition(['reported_ad.user_id' => Yii::$app->user->identity->id]);
        
    }

    public function getAllFollowingId($userId){
        $modleBlockedUser = new BlockedUser();
        $userIdsBlockedMe = $modleBlockedUser->getUserIdsWhomeBlockMe($userId); 
        $model = new Follower();
        
       return $model->find()->select('user_id')
        ->where(['follower_id'=>$userId])
        ->andWhere(['NOT',['user_id'=>$userIdsBlockedMe]])
        ->andWhere(['NOT',['type'=> Follower::FOLLOW_REQUEST]])
        ->column();
    }
    

    
}
