<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\LiveTvSubscriber;
use api\modules\v1\models\LiveTvViewer;
use api\modules\v1\models\Business;
use api\modules\v1\models\Category;
use api\modules\v1\models\Comment;
use api\modules\v1\models\UserFavorite;

class Coupon extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

   
    public $imageFile;  
    public $business_category_id;
    public $is_status;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'coupon';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [

            [['name', 'business_id'], 'required'],
            
            [['status', 'id','business_id','status','created_at','created_by','updated_at','updated_by','total_comment'], 'integer'],
            [['name','code','description','website_url'], 'string'],
            
            [['name','business_id'], 'required','on'=>['create','update']],
            
            [['imageFile','start_date','expiry_date','is_status'], 'safe'],
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
        //$fields[] = 'categoryName';

        $fields['businessName'] = (function($model){
            return @$model->business->name;
        });
        // strtotime("now")
        $fields['is_expired'] = (function($model){
            $expireDate = @$model->isExpire->expiry_date;
            $current_date = strtotime("now");
            $is_expire =1;
            if(!empty($expireDate > $current_date)){
                $is_expire =0;
            }
            return $is_expire;
        });
        $fields['is_favorite'] = (function($model){
            return (@$model->isFavorite) ? 1: 0;
        });
        return $fields;
    }


    public function extraFields()
    {
        return ['business'];
    }
   
 

    public function getBusiness(){

        return $this->hasOne(Business::className(), ['id' => 'business_id']);

    }

    public function getImageUrl()
    {
        if($this->image){
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_COUPON,$this->image);
           
        }else{
            return '';
        }
        
    }

    public function getIsExpire(){

        return $this->hasOne(Coupon::className(), ['id' => 'id']);

    }

    public function updateCommentCounter($couponId){

        $result = $this->findOne($couponId);
        if($result){
            $model = new Comment();
            $totalCount = (int)$model->find()->where(['reference_id'=>$couponId,'status'=>$model::STATUS_ACTIVE ,'type'=>$model::TYPE_COUPON])->count();
            
            $result->total_comment = $totalCount;
            if($result->save(false)){
            return  $totalCount;
            }else{
                return false;
            }
        }else{
            return false;
        }
              
    }

    public function getCouponMyFavorite()
    {
        return $this->hasMany(UserFavorite::className(), ['reference_id'=>'id'])->andOnCondition(['type'=>UserFavorite::TYPE_COUPON]);
        
    }
    
    public function getIsFavorite()
    {
        return $this->hasOne(UserFavorite::className(), ['reference_id'=>'id'])->andOnCondition(['type'=>UserFavorite::TYPE_COUPON,'user_favorite.user_id' => @Yii::$app->user->identity->id]);
        
    }

}
