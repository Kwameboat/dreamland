<?php
namespace api\modules\v1\models;
use Yii;
#use api\modules\v1\models\Interest;

class BlockedIp extends \yii\db\ActiveRecord
{
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'blocked_ip';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','created_by','created_at'], 'integer'],
            [['description','ip_address'], 'string'],
            
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app','ID'),
            
        ];
    }
    
    public function fields()
    {
        $fields = parent::fields();
  
        return $fields;
    }
    

}
