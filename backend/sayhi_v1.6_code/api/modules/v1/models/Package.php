<?php
namespace api\modules\v1\models;
use \yii\db\ActiveRecord;
use Yii;


class Package extends ActiveRecord
{
    
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

  
    const IS_DEFAULT_YES=1;
    const IS_DEFAULT_NO=0;
    

    

   
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
            [['name'], 'string', 'max' => 256]
       
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
    /*
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
    }*/


    
    public function getDefaultPackage()
    {
        
        return  $this->find()->where(['is_default'=>Package::IS_DEFAULT_YES,'status'=>Package::STATUS_ACTIVE])->one();
    }   

    
    public function getOrdinaryPackage()
    {
        return  $this->find()->where(['status'=>Package::STATUS_ACTIVE])->all();
    }

    

    /**
     * RELEATION START
     */
    
    
}
