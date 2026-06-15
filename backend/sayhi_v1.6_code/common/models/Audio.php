<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;


class Audio extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

   
    public $audioFile;
    public $imageFile;

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'audio';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name','artist','status','category_id'], 'required'],
            [['audioFile','imageFile'], 'required','on'=>'create'],
            [['audioFile'], 'file', 'skipOnEmpty' => true],
            [['imageFile'], 'file', 'skipOnEmpty' => true],
            
            [['status', 'id','category_id','duration'], 'integer'],
            [['name','artist','audio','image'], 'string', 'max' => 100]
           

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
            'audio' => Yii::t('app', 'Audio'),
            'category_id' => Yii::t('app', 'Category'),
            'image' => Yii::t('app', 'Thumbnail'),
            'imageFile' => Yii::t('app', 'Audio Thumbnail'),
            
            'artist' => Yii::t('app', 'Artist'),
            
        ];
    }
   
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
    }
    

    public function getStatus()
    {
       if($this->status==$this::STATUS_INACTIVE){
           return 'Inactive';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }
    }
    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_INACTIVE => 'Inactive');
    }
    
    
    public function getAudioUrl(){
        
        $audio = $this->audio;
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_REEL_AUDIO,$audio);


    }

    public function getImageUrl(){
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_REEL_AUDIO,$this->image);
        
    }

   /* public function getImageUrlBig(){
        
        $image = $this->image;
        if(empty($this->image)){
            $image  ='default.png';
        }
        return Yii::$app->urlManagerFrontend->baseUrl.'/uploads/banner/original/'.$image;
        
    }*/


    public function getAllAudio()
    {
        return $this->find()
        ->where(['status'=>$this::STATUS_ACTIVE])
        ->all();


    }
    public function getTotalAudioCount()
    {
        return Audio::find()->where(['<>','status',self::STATUS_DELETED])->count();
    }
   


    public function getCategory(){

        return $this->hasOne(Category::className(), ['id' => 'category_id']);

    }

}
