<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
//use api\modules\v1\models\CollectionUser;
use api\modules\v1\models\Follower;

use api\modules\v1\models\StoryView;
use api\components\VideoHelper;

class Story extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    public $stories;

    
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'story';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'id','user_id','created_at','type','video_time'], 'integer'],
            [['image','description','video','background_color'], 'string'],
            [['description'], 'string', 'max' => 200],
            [['type'], 'required','on'=>['create','update']],
            [['stories'], 'required','on'=>'createMain'],
            [['stories'], 'save'],

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

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->user_id       =   Yii::$app->user->identity->id;
          
        }
        return parent::beforeSave($insert);
    }

    
    public function fields()
    {
        
        $fields = parent::fields();
       // unset($fields['parent_id'],$fields['status'],$fields['priority'],$fields['leval']);
       $fields[] = "imageUrl"; 
       $fields[] = "videoUrl";
       $fields[] = "totalView";
       
        return $fields;
    }

    

    public function extraFields()
    {
        return ['user'];
    }

   

    public function getImageUrl(){
        if($this->image){
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_STORY,$this->image);

            //return Yii::$app->params['pathUploadStory'] ."/".$this->image;
        }
     }
     public function getVideoUrl(){
        if($this->video){
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_STORY,$this->video);
            //return Yii::$app->params['pathUploadStory'] ."/".$this->video;
        }
     }

     
    /**
     * RELEATION START
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }

     public function getFollowers()
    {
        return $this->hasMany(Follower::className(), ['user_id'=>'user_id'])->onCondition(['NOT',['follower.type'=> Follower::FOLLOW_REQUEST]]);
        
    }

    public function getTotalView()
    {
        return (int)$this->hasMany(StoryView::className(), ['story_id'=>'id'])->count();
        
    }

    public function getVideoDuration($videoUrl)
    {

        $videoInfo = VideoHelper::getVideoInfo($videoUrl);
        if(@$videoInfo['duration'] !== null) {
            return $videoInfo;
        }else {
        return false;
        }

    }


}
