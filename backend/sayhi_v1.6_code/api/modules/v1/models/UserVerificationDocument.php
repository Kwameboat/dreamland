<?php
namespace api\modules\v1\models;
use Yii;

class UserVerificationDocument extends \yii\db\ActiveRecord
{
    
    
    const MEDIA_TYPE_IMAGE = 1;
    const MEDIA_TYPE_VIDEO = 2;
    const MEDIA_TYPE_AUDIO = 3;

    public $filenameFile;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_verification_document';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [

            [['user_verification_id', 'id', 'media_type'], 'integer'],
            [['title','filename'], 'string', 'max' => 256],
            //[['filename'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png, jpg,mp4', 'on' => 'uploadFile'],
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
            'filename' => Yii::t('app', 'filename')
           

        ];
    }

    public function fields()
    {
        $fields = parent::fields();
        $fields[] = "filenameUrl";
     
        return $fields;
    }


    public function getFilenameUrl(){
        if($this->filename){
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_VERIFICATION,$this->filename);
        }
     }

     

    public function updateDocument($userVerificationId, $images)
    {
        //print_r($locations);

        // $images = json_decode($images);
        $values = [];

        $isDefaultSet = false;

//        $this->deleteAll( ['post_id' => $postId]);

        foreach ($images as $image) {
            //  print_r($location);
            $dataInner['user_verification_id'] = $userVerificationId;
            $dataInner['title'] = $image['title'];
            $dataInner['media_type'] = $image['media_type'];
            $dataInner['filename'] = $image['filename'];
            $dataInner['created_at'] = time();
            $values[] = $dataInner;
           // $isFirst = false;

        }

        if (count($values) > 0) {

            

            Yii::$app->db
                ->createCommand()
                ->batchInsert('user_verification_document', ['user_verification_id','title', 'media_type', 'filename','created_at'], $values)
                ->execute();
        }
    }

}
