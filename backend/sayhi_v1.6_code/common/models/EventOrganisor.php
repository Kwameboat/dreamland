<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use common\models\Event;


/**
 * This is the model class 
 *
 */
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
            
            [[ 'name' ], 'required','on'=>['create','update']],
            //[['imageFile'], 'required','on'=>'create'],

            [['imageFile'], 'file', 'skipOnEmpty' => true],
            

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
           
            'status' => Yii::t('app', 'Status'),
            'imageFile' => Yii::t('app', 'Image')
            
            
            
            
            
            
        ];
    }
    
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
        }else{
            $this->updated_at = time();
        }
        return parent::beforeSave($insert);
    }

  
    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_INACTIVE => 'Inactive');
    }

    public function getStatus()
    {
       if($this->status==$this::STATUS_INACTIVE){
           return 'Inactive';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }
    }
  
    
    public function getEvent()
    {
        return $this->hasOne(Event::className(), ['id' => 'event_id']);

    }

    public function getImageUrl()
    {
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_EVENT_ORGANISOR,$this->image);

        
    }

   



    

}
