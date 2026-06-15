<?php
namespace backend\models;
use Yii;
class DatingSubscriptionPackage extends \yii\db\ActiveRecord
{
    
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;


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
            [['name','number_of_profiles','duration','coin', 'status'], 'required'],
           
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app','ID'),
            'coin' => Yii::t('app','Coins'),
            'number_of_profiles' => Yii::t('app','Number Of Profiles'),
            'name' => Yii::t('app','Package Name'),
            'duration' => Yii::t('app','Duration'),
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

    public function getDurationDropDownData()
    {
        return array(self::DATING_SUBSCRIPTION_ONE_WEEK => 'Weekly', self::DATING_SUBSCRIPTION_ONE_MONTH => '1 Month' , self::DATING_SUBSCRIPTION_THREE_MONTH => '3 Month' , self::DATING_SUBSCRIPTION_SIX_MONTH => '6 Month' , self::DATING_SUBSCRIPTION_ONE_YEAR => '1 Year');
    }

    public function getDurationData()
    {
       if($this->duration==$this::DATING_SUBSCRIPTION_ONE_WEEK){
           return 'Weekly';
       }else if($this->duration==$this::DATING_SUBSCRIPTION_ONE_MONTH){
           return '1 Month';    
       }else if($this->duration==$this::DATING_SUBSCRIPTION_THREE_MONTH){
            return '3 Month';    
        }
        else if($this->duration==$this::DATING_SUBSCRIPTION_SIX_MONTH){
            return '6 Month';    
        }
        else if($this->duration==$this::DATING_SUBSCRIPTION_ONE_YEAR){
            return '1 Year';    
        }
       
    }

}
