<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use common\models\User;
use common\models\Post;

class PostGallary extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE = 10;
    const STATUS_DELETED = 0;

    const IS_DEFAULT_YES = 1;
    const IS_DEFAULT_NO = 0;

    
    const MEDIA_TYPE_IMAGE = 1;
    const MEDIA_TYPE_VIDEO = 2;
    const MEDIA_TYPE_AUDIO = 3;
    const MEDIA_TYPE_GIF = 4;


    const TYPE_POST = 1;
    const TYPE_COMPETITION = 2;
    public $filenameFile;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'post_gallary';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [

            [['post_id', 'status', 'id', 'type', 'media_type','is_default'], 'integer'],
            [['filename','video_thumb'], 'string', 'max' => 256],
          //  [['filename'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png, jpg,mp4', 'on' => 'uploadFile'],
            //[['filename'], 'file', 'skipOnEmpty' => false, 'extensions' => 'mp4', 'maxSize' => '2048000', 'on' => 'uploadVideo'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'filename' => Yii::t('app', 'filename'),
            'status' => Yii::t('app', 'Status'),

        ];
    }

   


     public function getFilenameUrl(){
        if($this->filename){
            if($this->media_type == PostGallary::MEDIA_TYPE_GIF){
                
                return $this->filename;
            }else{
                return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_POST,$this->filename);
            }
            
        }
       
     }

     public function getVideoThumbUrl(){
        if($this->video_thumb){
            
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_POST,$this->video_thumb);

        }
     }
     public function updateGallary($postId, $images)
     {
         //print_r($locations);
 
         // $images = json_decode($images);
         $values = [];
 
         $isDefaultSet = false;
 
         $this->deleteAll( ['post_id' => $postId]);
 
         foreach ($images as $image) {
             // echo "<pre>";
             // print_r($image);
             // exit;
             //  print_r($location);
             $dataInner['post_id'] = $postId;
             $dataInner['type'] = 1;
             $dataInner['media_type'] = $image['fileType'];
             $dataInner['filename'] = $image['file'];
             $dataInner['video_thumb'] = '';
 
             if (!$isDefaultSet) {
                 $isDefaultSet = true;
                 $dataInner['is_default'] = PostGallary::IS_DEFAULT_YES;
             } else {
                 $dataInner['is_default'] = PostGallary::IS_DEFAULT_NO;
 
             }
             $dataInner['created_at'] = time();
             $dataInner['width'] = isset($image['width'])? $image['width']:0;
             $dataInner['height'] = isset($image['height'])? $image['height']:0;
             $values[] = $dataInner;
            // $isFirst = false;
 
         }
 
         if (count($values) > 0) {
 
             
 
             Yii::$app->db
                 ->createCommand()
                 ->batchInsert('post_gallary', ['post_id','type', 'media_type', 'filename','video_thumb','is_default', 'created_at','width','height'], $values)
                 ->execute();
         }
     }


   
}
