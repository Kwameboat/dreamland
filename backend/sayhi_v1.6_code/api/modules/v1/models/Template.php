<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "countryy".
 *
 */
class Templage extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

   
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'template';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'status'], 'required'],
            [['status', 'id','priority'], 'integer'],
            [['name'], 'string', 'max' => 100],
           // [['name', 'status'], 'save'],

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
            
            
        ];
    }
    
   
    public function getTemolate(){
        return $this->find()->select(['id','name'])->where(['status'=>$this::STATUS_ACTIVE])->all();
        
    }
    

    

}
