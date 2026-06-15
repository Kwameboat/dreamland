<?php
namespace api\modules\v1\models;
use Yii;

class HashTag extends \yii\db\ActiveRecord
{
    public $counter;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'hash_tag';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['hash_tag','post_id'], 'required'],
            [['post_id','counter'], 'integer'],
            [['hash_tag'], 'string', 'max' => 100]
            
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app','ID'),
            'post_id' => Yii::t('app','Post Id'),
            'hash_tag' => Yii::t('app','Hash Tag')
            
        ];
    }

    public function fields()
    {
        $fields = parent::fields();

      
     //  $fields[] = 'counter';

       $fields['counter'] = (function($model){
            
          return (int)$model->counter;
       });
       

       
     //  $fields[] = 'userLocation';
        return $fields;
    }


    

    public function updateHashTag($postId,$hashTag){
        //print_r($locations);


        $hashTags = explode(',',$hashTag);
        if(count($hashTags) >0){
            $hashTags = array_unique($hashTags);
        }
        
        $values=[];
        
        foreach($hashTags as $hashTag){
          
          
            $locationValue['post_id']           =   $postId;
            $locationValue['hashtag']           =   $hashTag;
            $values[]=$locationValue;

        }   

        if(count($values)>0){

            /*if($type==UserLocation::TYPE_USER){
                $this->updateAll(['status'=>UserLocation::STATUS_DELETED],['user_id'=>$userId,'type'=>UserLocation::TYPE_USER]);
            }elseif($type==UserLocation::TYPE_AD){
                $this->updateAll(['status'=>UserLocation::STATUS_DELETED],['ad_id'=>$adId,'type'=>UserLocation::TYPE_AD]);
            }*/
         

            Yii::$app->db
            ->createCommand()
            ->batchInsert('hash_tag', ['post_id','hashtag'],$values)
            ->execute();
        }
    }

    public function getPost()
    {

        return  $this->hasMany(Post::className(), ['id'=>'post_id']);
        
        
    }


    

}
