<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;



class EventOrganisor extends \yii\db\ActiveRecord
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
        return 'event_organisor';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
             
            [['status', 'id','created_at','updated_at'], 'integer'],
            [['name','image'], 'string'],
            
         
            [['imageFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg'],
            
            

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
        $fields[] = 'imageUrl';
        return $fields;
    }

    public function getImageUrl()
    {
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_EVENT_ORGANISOR,$this->image);
    }
   
    

}
