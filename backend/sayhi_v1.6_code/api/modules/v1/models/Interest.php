<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;

class Interest extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

   
    public $imageFile;
    public $transaction_id;
   

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'interest';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'status','name'], 'required'],
            
            [['status', 'id'], 'integer'],
            [['name'], 'string'],
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
        $fields[] = 'imageUrl';

        return $fields;
    }

    public function getImageUrl()
    {
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_INTEREST,$this->image);

        
    }
}
