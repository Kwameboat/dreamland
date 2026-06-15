<?php
namespace common\models;
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
            [['status', 'id','event_id'], 'integer'],
            [['image',], 'string', 'max' => 100]

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'image' => Yii::t('app', 'Image'),
        ];
    }


    
    public function addPhoto($id,$images){
        //print_r($locations);
        $values=[];

        
        
        foreach($images as $image){
          //  print_r($location);
            $dataInner=[]; 
            $dataInner['event_id']         =    $id;
            $dataInner['image']                  =    $image;
            $dataInner['created_at']                  =   time();
           
            $values[]=$dataInner;

        }   

        
        if(count($values)>0){

           // $this->updateAll(['status'=>AdImage::STATUS_DELETED],['ad_id'=>$adId]);

            Yii::$app->db
            ->createCommand()
            ->batchInsert('event_gallary_image', ['event_id','image','created_at'],$values)
            ->execute();
        }
    }

   
   
    public function getImageUrl(){

        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_EVENT,$this->image);
        
    }
    

}
