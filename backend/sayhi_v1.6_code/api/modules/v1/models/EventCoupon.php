<?php
namespace api\modules\v1\models;

use Yii;
use api\modules\v1\models\EventGallaryImage;
//use api\modules\v1\models\EventTicket;


class EventCoupon extends \yii\db\ActiveRecord
{
    const COMMON_NO = 0;
    const COMMON_YES = 1;
    
    
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_DEACTIVE = 9;
    public $imageFile;


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
            
           [['title','code','expiry_date'], 'required','on'=>['create']],
           
           [['id','title','code','expiry_date'], 'required','on'=>['update']],
        
            [['imageFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg'],
           // [['code'], 'checkUniqueCouponCode', 'on'=>['create','update']],
            

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
        $fields[] = 'imageUrl';
        
        return $fields;
    }

    public function extraFields()
    {
       // return ['eventTicket'];
    }

    public function getImageUrl()
    {
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_COUPON,$this->image);
    }

    

    /**
     * RELEATION START
     */
    
    



}
