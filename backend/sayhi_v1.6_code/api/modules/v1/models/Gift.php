<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;


use api\modules\v1\models\GiftCategory;
use api\modules\v1\models\GiftHistory;


class Gift extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

   
   
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gift';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            

            [['status', 'id','category_id','coin'], 'integer'],
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
        $fields[] = 'imageUrl';
        //$fields[] = 'categoryName';

        $fields['categoryName'] = (function($model){
            return @$model->category->name;
           // return (@$model->isReported) ? 1: 0;
        });
      
        return $fields;
    }


    public function extraFields()
    {
        return ['subCategory'];
    }
   
 

    public function getCategory(){

        return $this->hasOne(GiftCategory::className(), ['id' => 'category_id']);

    }

    public function getGiftHistory(){

        return $this->hasMany(GiftHistory::className(), ['gift_id' => 'id']);

    }
    

 

    public function getImageUrl()
    {
        if($this->image){
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_GIFT,$this->image);
           
        }else{
            return '';
        }
        
    }
    

    

}
