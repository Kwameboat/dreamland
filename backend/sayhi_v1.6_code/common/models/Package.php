<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;




/**
 * This is the model class 
 *
 */
class Package extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    const COMMON_NO=0;
    const COMMON_YES=1;

    
    public $imageFile;

    
    
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
            [['name', 'status'], 'required'],
            
            [['status', 'id','coin','created_at','created_by','updated_at','updated_by'], 'integer'],
            [['name','in_app_purchase_id_ios','in_app_purchase_id_android'], 'string'],
            

            
            [['price','is_default'], 'safe'],

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
            'coin' => Yii::t('app', 'Coin'),

            
            
        ];
    }

   

    public function getPackageDetails($packageId)
    {
      return Package::find()->where(['id'=>$packageId])->one(); 
      
    }


    

}
