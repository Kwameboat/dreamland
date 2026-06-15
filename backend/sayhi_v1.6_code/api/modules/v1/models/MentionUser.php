<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\User;

class MentionUser extends \yii\db\ActiveRecord
{
    //public $counter;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mention_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['username','post_id'], 'required'],
            [['post_id','user_id','id'], 'integer'],
            [['username'], 'string']
            
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

      
     //  $fields[] = 'counter';

      /* $fields['counter'] = (function($model){
            
          return (int)$model->counter;
       });*/
       

       
     //  $fields[] = 'userLocation';
        return $fields;
    }


    

    public function updateMentionUser($postId,$mentionUsersStr){
        //print_r($locations);
        $modelUser =  new User();

        $mentionUsers = explode(',',$mentionUsersStr);
        $values=[];

        $userIds=[];
        
        foreach($mentionUsers as $username){

            $user = $modelUser->find()->where(['username'=>$username])->one();
            if($user){

                $userIds[] = $user->id;
            
                $locationValue['post_id']           =   $postId;
                $locationValue['user_id']           =   $user->id;
                $locationValue['username']           =   $user->username;
                $values[]=$locationValue;
            }

        }   

        if(count($values)>0){

            /*if($type==UserLocation::TYPE_USER){
                $this->updateAll(['status'=>UserLocation::STATUS_DELETED],['user_id'=>$userId,'type'=>UserLocation::TYPE_USER]);
            }elseif($type==UserLocation::TYPE_AD){
                $this->updateAll(['status'=>UserLocation::STATUS_DELETED],['ad_id'=>$adId,'type'=>UserLocation::TYPE_AD]);
            }*/
         

            Yii::$app->db
            ->createCommand()
            ->batchInsert('mention_user', ['post_id','user_id','username'],$values)
            ->execute();

            
        }
        return $userIds;
    }


    

}
