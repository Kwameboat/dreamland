<?php
namespace backend\models;
use Yii;
class Package extends \yii\db\ActiveRecord
{
    
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    const TYPE_ORDINARY=1;
    const TYPE_BANNER=2;

   
  //  public $imageFile;

    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'package';
    }

    

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['coin','is_default', 'status', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['price'], 'number'],
            [['name','in_app_purchase_id_ios','in_app_purchase_id_android'], 'string', 'max' => 256],
            [['name','price','coin','is_default', 'status','in_app_purchase_id_ios','in_app_purchase_id_android'], 'required'],
           
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app','ID'),
            'coin' => Yii::t('app','Coin Available'),
            'price' => Yii::t('app','Price'),
            'name' => Yii::t('app','Name'),
            'is_default' => Yii::t('app','Is Default'),
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
            $this->created_by =   Yii::$app->user->identity->id;
          
        }else{

           
            $this->updated_at = time();
            $this->updated_by =   Yii::$app->user->identity->id;

        }
        return parent::beforeSave($insert);
    }


    public function getStatusString()
    {
       if($this->status==$this::STATUS_INACTIVE){
           return 'Inactive';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }
    }
    public function getIsDefaultString()
    {
       if($this->is_default==0){
           return 'No';
       }else if($this->is_default==1){
           return 'Yes';    
       }
    }

    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_INACTIVE => 'Inactive');
    }

    public function getIsDefaultDropDownData()
    {
        return array(0 => 'No', 1 => 'Yes');
    }


    
}
