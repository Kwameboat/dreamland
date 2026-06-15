<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\LiveTv;
use api\modules\v1\models\Event;

use api\modules\v1\models\Job;
use api\modules\v1\models\Ad;

class Category extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    const LEVEL_MAIN = 1;
    const LEVEL_SUB = 2;
    
    const TYPE_EVENT = 1;

    const TYPE_REEL_AUDIO = 4;
    const TYPE_FUNDRASING=5;
    const TYPE_PODCAST_SHOW=6;

    const TYPE_POLL= 7;

    const TYPE_BUSINESS_CATEGORY=8;
    const TYPE_AD_CATEGORY=9;
    const TYPE_JOB_CATEGORY=10;

    

    public $imageFile;
   

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'category';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'status'], 'required'],
            [['imageFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg'],
            [['status', 'id','parent_id','priority','type'], 'integer'],
            [['name'], 'string', 'max' => 100],
           // [['name', 'status'], 'save'],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => Yii::t('app', 'Name'),
            'status' => Yii::t('app', 'Status'),
            'parent_id' => Yii::t('app', 'Main Category'),
            
        ];
    }
    
    public function fields()
    {
        
        $fields = parent::fields();
        unset($fields['parent_id'],$fields['status'],$fields['priority'],$fields['leval']);
        $fields[] = 'imageUrl';
        $fields['total_business'] = (function($model){
            return @$model->totalBusiness;
        });
        $fields['total_coupon'] = (function($model){
            return @$model->totalCoupon;
        });
        return $fields;
        
    }


    public function extraFields()
    {
        return ['subCategory','liveTv','event','pollList','totalCampaign','campaignList','business','coupon','totalPodcastShow','totalJob','total_ads'];
    }
   
    public function getMainCategory(){
        return $this->find()->select(['id','name','image'])->where(['status'=>$this::STATUS_ACTIVE,'level'=>$this::LEVEL_MAIN])->all();
        
    }
    /*public function getSubCategory($parentId){
        return $this->find()->select(['id','name'])->where(['status'=>$this::STATUS_ACTIVE,'level'=>$this::LEVEL_SUB,'parent_id'=>$parentId])->all();
        
    }*/
    public function getParent(){

        return $this->hasOne(Category::className(), ['id' => 'parent_id']);

    }

    public function getSubCategory(){

        return $this->hasMany(Category::className(), ['parent_id' => 'id'])->from(['subCategory' => Category::tableName()])->select(['id','name','parent_id']);

    }

    public function getImageUrl()
    {
        if($this->image){
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_CATEGORY,$this->image);

           
        }else{
            return '';
        }
        
    }

    public function getLiveTv()
    {
        return $this->hasMany(liveTv::className(), ['category_id' => 'id'])->andOnCondition(['live_tv.status' => liveTv::STATUS_ACTIVE])->limit(10);

    }
    public function getEvent()
    {
        return $this->hasMany(Event::className(), ['category_id' => 'id'])->andOnCondition(['event.status' => Event::STATUS_ACTIVE])->limit(10);

    }

    public function getCampaignList()
    {
        return $this->hasMany(Campaign::className(), ['category_id' => 'id'])->andOnCondition(['campaign.status' => Campaign::STATUS_ACTIVE])->limit(10);

    }
    public function getPollList()
    {
        return $this->hasMany(Poll::className(), ['category_id' => 'id'])->andOnCondition(['poll.status' => Poll::STATUS_ACTIVE])->orderBy(['id'=> SORT_DESC])->limit(10);

    }
    
    public function getTotalCampaign()
    {
        return $this->hasMany(Campaign::className(), ['category_id' => 'id'])->andOnCondition(['campaign.status' => Campaign::STATUS_ACTIVE])->count();

    }

    public function getTotalCoupon()
    {
        return $this->hasMany(Coupon::className(), ['business_id' => 'id'])->andOnCondition(['status'=>Coupon::STATUS_ACTIVE])
        ->viaTable('business', ['business_category_id' => 'id'])->count();

    }
    public function getCoupon()
    {
        return $this->hasMany(Coupon::className(), ['business_id' => 'id'])->andOnCondition(['status'=>Coupon::STATUS_ACTIVE])
        ->viaTable('business', ['business_category_id' => 'id'])->orderBy(['id' => SORT_DESC])->limit(5);
    }

    public function getTotalBusiness()
    {
        return $this->hasMany(Business::className(), ['business_category_id' => 'id'])->andOnCondition(['business.status' => Business::STATUS_ACTIVE])->count();
    }

    public function getBusiness()
    {
        return $this->hasMany(Business::className(), ['business_category_id' => 'id'])->andOnCondition(['business.status' => Business::STATUS_ACTIVE])->orderBy(['id' => SORT_DESC])->limit(5);

    }
    public function getTotalPodcastShow()
    {
        return (int)$this->hasMany(PodcastShow::className(), ['category_id' => 'id'])->andOnCondition(['podcast_show.status' => PodcastShow::STATUS_ACTIVE])->count();

    }

    public function getTotalJob()
    {
        return (int)$this->hasMany(Job::className(), ['category_id' => 'id'])->andOnCondition(['jobs.status' => Job::STATUS_ACTIVE])->count();

    }

    public function getTotal_ads()
    {
        return (int)$this->hasMany(Ad::className(), ['sub_category_id' => 'id'])->andOnCondition(['ad.status' => Ad::STATUS_ACTIVE])->count();

    }
    

}
