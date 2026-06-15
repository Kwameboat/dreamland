<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;


use api\modules\v1\models\GiftCategory;
use api\modules\v1\models\GiftHistory;


class GiftTimeline extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

   
   
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gift_timeline';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            

            [['status', 'id','is_paid','coin'], 'integer'],
            [['name'], 'string']
          
            
            
            
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
        unset($fields['parent_id'],$fields['status'],$fields['priority'],$fields['leval']);
        
      
        return $fields;
    }


    public function extraFields()
    {
        return ['subCategory'];
    }
    

    

}
