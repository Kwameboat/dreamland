<?php
namespace common\models;
//use common\models\User;
use common\models\CompetitionUser;
use common\models\Post;

use common\models\CompetitionExampleImage;
use common\models\CompetitionPosition;
use common\models\Setting;
use Yii;


class EventCoupon extends \yii\db\ActiveRecord
{
    
    const COMMON_NO = 0;
    const COMMON_YES = 1;
    
    
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_DEACTIVE = 9;
    //const STATUS_COMPLETED = 10;

    
    public $imageFile;
    
    //public $competition_id;


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event_coupon';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'id','created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['title','subtitle','description','image','code','expiry_date'], 'string'],
            [['minimum_order_price','coupon_value'], 'number'],
            
            [['title','code','expiry_date'], 'required','on'=>['create','update']],
           // [['imageFile'], 'required','on'=>'create'],

            [['imageFile'], 'file', 'skipOnEmpty' => true],
            [['code'], 'checkUniqueCouponCode', 'on'=>['create','update']],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'subtitle' => Yii::t('app', 'Sub title'),
            'status' => Yii::t('app', 'Status'),
            
            'image' => Yii::t('app', 'Coupon image'),
            'imageFile' => Yii::t('app', 'Coupon image')
            
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
   /* public function checkEndDate($attribute, $params, $validator)
    {
        if(!$this->hasErrors()){
            if($this->start_date > $this->end_date ){
                $this->addError($attribute, Yii::t('app','End date must be greater than start date'));  
            }
        
            
        }
       
    }*/
    public function checkUniqueCouponCode($attribute, $params, $validator)
    {
        if(!$this->hasErrors()){
            if($this->isNewRecord){
                $count= EventCoupon::find()->where([$attribute=>$this->$attribute])->andWhere(['<>','status',self::STATUS_DELETED])->count();
            }else{
                $count= EventCoupon::find()->where([$attribute=>$this->$attribute])->andWhere(['<>','id',$this->id])->andWhere(['<>','status',self::STATUS_DELETED])->count();
            }
            
            if($count){
                $this->addError($attribute, Yii::t('app','Coupon code already exist'));     
            }
            
        }
       
    }

    
    public function getStatusString()
    {
       if($this->status==$this::STATUS_DEACTIVE){
           return 'Deactive';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }
    }

    public function getStatusButton()
    {
        if($this->status==$this::STATUS_ACTIVE){
         //  return 'Active';   
            $currentTime= time();
            if($this->expiry_date > $currentTime){
                return'<button type="button" class="btn btn-sm active_btn">'.Yii::t('app','Active').'</button>';      
                //return'<button type="button" class="btn btn-sm expired_btn">'.Yii::t('app','Inactive').'</button>'; 
            }else{
                return'<button type="button" class="btn btn-sm expired_btn">'.Yii::t('app','Expired').'</button>'; 
            }


           
        }else if($this->status==$this::STATUS_DELETED){
            
            return'<button type="button" class="btn btn-sm expired_btn">'.Yii::t('app','Deleted').'</button>'; 
        }else if($this->status==$this::STATUS_DEACTIVE){
            
            return'<button type="button" class="btn btn-sm expired_btn">'.Yii::t('app','Deactive').'</button>'; 
        }
        
       
    }


 


    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_DEACTIVE => 'Deactive');
    
    }

    /*
    
    public function getAwardTypeData()
    {
        return array(self::AWARD_TYPE_PRICE => 'Price', self::AWARD_TYPE_COIN => 'Coin');
    }
    */


    public function getImageUrl()
    {
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_COUPON,$this->image);

        
    }
    
   


}
