<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\Post;

class Audio extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

   
    public $audioFile;

    
    
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
            [['name', 'status'], 'required'],
            [['audioFile'], 'required','on'=>'create'],
            [['audioFile'], 'file', 'skipOnEmpty' => true],
            [['status', 'id'], 'integer'],
            [['name'], 'string', 'max' => 100]
           

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


    public function fields()
    {
        $fields = parent::fields();

       $fields[] = 'audio_url';
       $fields[] = 'image_url';
       $fields[] = 'reelCount';
       //$fields[cate] = 'getuserLocation';
        return $fields;
    }
    
    

   

    
    public function getAudio_url(){
        
       $audio = $this->audio;
       
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_REEL_AUDIO,$audio);
       
        
    }
    public function getImage_url(){
        
        
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_REEL_AUDIO,$this->image);
    }

    public function getAllAudio($categoryId)
    {
        $query = $this->find()
        ->select(['id','category_id','name','audio'])
        ->where(['status'=>$this::STATUS_ACTIVE]);
        
        if($categoryId>0){
            $query->andWhere(['category_id'=>$categoryId]);
        }

        return $query->all();


    }

    public function getReelCount()
    {
        
        return (int)$this->hasOne(Post::className(), ['audio_id' => 'id'])->count();

    }

   

    

}
