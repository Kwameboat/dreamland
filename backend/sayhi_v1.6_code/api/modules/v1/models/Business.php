<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\Category;
use api\modules\v1\models\Coupon;
use api\modules\v1\models\BusinessImages;

class Business extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

   
    public $imageFile;  

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'business';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [

            [['name', 'business_category_id'], 'required'],
            
            [['status', 'id','business_category_id','price_range_from','price_range_to','created_by','created_at','phone','updated_at','updated_by'], 'integer'],
            [['name','open_time','close_time','address','location','latitude','longitude','description','city'], 'string'],
            
            [['name','business_category_id'], 'required','on'=>['create','update']],
            
            [['imageFile',], 'safe'],
            [['imageFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg,jpeg'],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            
        ];
    }
    
    public function fields()
    {
        
        $fields = parent::fields();
        unset($fields['parent_id'],$fields['status'],$fields['priority'],$fields['leval']);
        $fields[] = 'imageUrl';
        $fields['businessAllImage'] = (function($model){
            return @$model->bussinessAllImg;
        });
        //$fields[] = 'categoryName';
        $fields['is_favorite'] = (function($model){
            return (@$model->isFavorite) ? 1: 0;
        });
        $fields['businessCategoryName'] = (function($model){
            return @$model->category->name;
        });

        $fields['total_coupon'] = (function($model){
            return (@$model->allCoupon) ? @$model->allCoupon:0;
        });

        return $fields;
    }


    public function extraFields()
    {
        return ['coupon','category'];
    }
   
 

    public function getCategory(){

        return $this->hasOne(Category::className(), ['id' => 'business_category_id']);

    }

    public function getImageUrl()
    {
        if($this->image){
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_BUSINESS,$this->image);
           
        }else{
            return '';
        }
        
    }

    public function getAllCoupon(){

        return (int)$this->hasMany(Coupon::className(), ['business_id' => 'id'])->andOnCondition(['status'=>Coupon::STATUS_ACTIVE])->count();
         // return (int)$this->hasMany(Coupon::className(), ['business_id' => 'id'])->where([">",'expiry_date',strtotime("now")])->andWhere(['status'=>Coupon::STATUS_ACTIVE])->count();
    }

    public function getCoupon(){

        return $this->hasMany(Coupon::className(), ['business_id' => 'id'])->andOnCondition(['status'=>Coupon::STATUS_ACTIVE])->orderBy(['id' => SORT_DESC])->limit(5);

    }

    public function getIsFavorite()
    {
        return $this->hasOne(UserFavorite::className(), ['reference_id'=>'id'])->andOnCondition(['type'=>UserFavorite::TYPE_BUSINESS,'user_favorite.user_id' => @Yii::$app->user->identity->id]);
        
    }

    public function getBussinessAllImg()
    {
        return $this->hasMany(BusinessImages::className(), ['business_id' => 'id']);

    }

    public function getBusinessMyFavorite()
    {
        return $this->hasMany(UserFavorite::className(), ['reference_id'=>'id'])->andOnCondition(['type'=>UserFavorite::TYPE_BUSINESS]);
        
    }

}
