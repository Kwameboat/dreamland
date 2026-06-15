<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;


class EventGallaryImage extends \yii\db\ActiveRecord
{
    const STATUS_DELETED=0;
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event_gallary_image';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'id'], 'integer'],
            [['image',], 'string', 'max' => 100]

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
        //$fields[] = 'audio_url';
       // $fields[] = 'imageUrl';
       //$fields[cate] = 'getuserLocation';
        return $fields;
    }
   
    public function getImageUrl(){
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_EVENT,$this->image);
        
        //return Yii::$app->params['pathUploadCompetition'] ."/".$this->image;
    }
    
    public function addPhoto($id,$images){

        $values=[];

        $this->deleteAll( ['event_id' => $id]);
        
        foreach($images as $image){
            $dataInner=[]; 
            $dataInner['event_id']         =    $id;
            $dataInner['image']                  =    $image;
            $dataInner['created_at']                  =   time();
           
            $values[]=$dataInner;

        }   

        
        if(count($values)>0){

            Yii::$app->db
            ->createCommand()
            ->batchInsert('event_gallary_image', ['event_id','image','created_at'],$values)
            ->execute();
        }
    }
}
