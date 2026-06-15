<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;

class AdImage extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_DELETED=0;
    
    const IS_DEFAULT_YES=1;
    const IS_DEFAULT_NO=0;
    public  $imageFile;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ad_image';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            
            [['ad_id','status', 'id'], 'integer'],
            [['image'], 'string', 'max' => 256],
            [['imageFile'], 'file', 'skipOnEmpty' => false],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app','ID'),
            'image' => Yii::t('app','Image'),
            'status' => Yii::t('app','Status'),
            
        ];
    }

    public function updateAdImages($adId,$images){
        //print_r($locations);
        $values=[];

        $isFirst=true;
        
        foreach($images as $image){
          //  print_r($location);
            $dataInner['ad_id']             =$adId;
            $dataInner['image']        =    $image;
            if($isFirst){
                $dataInner['is_default']        =   AdImage::IS_DEFAULT_YES;    
            }else{
                $dataInner['is_default']        =   AdImage::IS_DEFAULT_NO;    
               
            }
            $dataInner['created_at']        =   time();
            $values[]=$dataInner;
            $isFirst=false;

        }   

        if(count($values)>0){

            $this->updateAll(['status'=>AdImage::STATUS_DELETED],['ad_id'=>$adId]);

            Yii::$app->db
            ->createCommand()
            ->batchInsert('ad_image', ['ad_id','image','is_default','created_at'],$values)
            ->execute();
        }
    }

    
}
