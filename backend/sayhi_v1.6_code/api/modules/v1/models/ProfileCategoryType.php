<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;

class ProfileCategoryType extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'profile_category_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[ 'status','name'], 'required'],
            
            [['status', 'id'], 'integer'],
            // [['title','description'], 'string'],
            [['image'], 'safe'],
            
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [ ];
    }
    
    public function fields()
    {
        
        $fields = parent::fields();
        // unset($fields['parent_id'],$fields['status'],$fields['priority'],$fields['leval']);
        //$fields[] = 'categoryName';
        $fields[] = 'imageUrl';
        return $fields;
    }

    public function getImageUrl()
    {
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_CATEGORY,$this->image);

        
    }

}
