<?php
namespace api\modules\v1\models;
use \yii\db\ActiveRecord;
use Yii;


class DatingSubscriptionPackage extends ActiveRecord
{
    
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

  
    const IS_DEFAULT_YES=1;
    const IS_DEFAULT_NO=0;
    
    const DATING_SUBSCRIPTION_ONE_WEEK=1;
    const DATING_SUBSCRIPTION_ONE_MONTH=2;
    const DATING_SUBSCRIPTION_THREE_MONTH=3;
    const DATING_SUBSCRIPTION_SIX_MONTH=4;
    const DATING_SUBSCRIPTION_ONE_YEAR=5;
    

   
  //  public $imageFile;

    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dating_subscription';
    }

    

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['coin','is_default', 'status', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['name'], 'string', 'max' => 256],
            // [['name','number_of_profiles','duration','coin', 'status'], 'required'],
            [['dating_subscription_id'], 'required','on'=>'datingSubscription'],
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

        // remove fields that contain sensitive information
        unset($fields['created_at'], $fields['created_by'], $fields['updated_at'], $fields['updated_by']);
      
        return $fields;
    }
    
    public function getDefaultPackage()
    {
        
        return  $this->find()->where(['is_default'=>Package::IS_DEFAULT_YES,'status'=>Package::STATUS_ACTIVE])->one();
    }   

    
    public function getSubscriptionPackage()
    {
        return  $this->find()->where(['status'=>DatingSubscriptionPackage::STATUS_ACTIVE])->all();
    }

    

    /**
     * RELEATION START
     */
    
    
}
