<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

class PickleballCourt extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    const TYPE_OUTDOOR=1;
    const TYPE_INDOOR=2;

    public $imageFile;

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pickleball_court';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[ 'name','address','latitude','longitude','image'], 'string'],
            [['id','type','status', 'created_at','created_by','rating'], 'integer'],
            [['name','type','address','latitude','longitude'], 'required','on'=>'create'],
            [['name','type','address','latitude','longitude'], 'required','on'=>'update'],
            //[['imageFile'], 'required','on'=>'createMainCategory'],
            [['imageFile','image'], 'safe'],

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
    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_INACTIVE => 'Inactive');
    }
    public function getTypeDropDownData()
    {
        return array( self::TYPE_INDOOR => 'Indoor',self::TYPE_OUTDOOR => 'Outdoor');
    }

    public function getStatus()
    {
       if($this->status==$this::STATUS_INACTIVE){
           return 'Inactive';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }
    }
    public function getType()
    {
       if($this->type==$this::TYPE_OUTDOOR){
           return 'Outdoor';
       }else if($this->type==$this::TYPE_INDOOR){
           return 'Indoor';    
       }
    }
   
    
    public function getImageUrl()
    {
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_PICKLEBALL_COURT,$this->image);

        
    }

   



    

}
