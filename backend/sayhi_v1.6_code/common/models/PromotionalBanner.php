<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;

class PromotionalBanner extends \yii\db\ActiveRecord
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
        return 'promotional_banner';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'status'], 'required'],
            // 'in_app_purchase_id_ios','in_app_purchase_id_android','ad_fee'
            [['imageFile'], 'required','on'=>'create'],
            [['imageFile'], 'file', 'skipOnEmpty' => true],
            [['status', 'id'], 'integer'],
            [['ad_fee'], 'number'],
            
            [['name','in_app_purchase_id_ios','in_app_purchase_id_android'], 'string', 'max' => 250]
           

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
            'image' => Yii::t('app', 'Image'),
            
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
    
    public function getImageUrl(){
        
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_PROMOTIONAL_BANNER,$this->image);
        
    }


    public function getAllPromotionalBanner()
    {
        return $this->find()
        ->where(['status'=>$this::STATUS_ACTIVE])
        ->all();


    }

    

}
