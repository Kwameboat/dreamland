<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\Post;


class CompetitionUser extends \yii\db\ActiveRecord
{
    const STATUS_DELETED=0;
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'competition_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'id','user_id','competition_id','is_winner','created_at','updated_at'], 'integer'],
           // [['image',], 'string', 'max' => 100]

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
           
        } else {
            $this->updated_at = time();
           
        }

        return parent::beforeSave($insert);
    }

   
   
    public function fields()
    {
        $fields = parent::fields();
        //$fields[] = 'audio_url';
     //   $fields[] = 'imageUrl';
        $fields['userDetail'] = 'userDetail';
        $fields['post'] = 'post';
       //$fields[cate] = 'getuserLocation';
        return $fields;
    }

    public function extraFields()
    {
        return ['userDetail'];
    }
    
   /*
    public function getImageUrl(){
        
        return Yii::$app->params['pathUploadCompetition'] ."/".$this->image;
    }
    */

    
  
    /**
     * RELEATION START
     */
    public function getUserDetail()
    {
        return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }
    /*public function getPost()
    {
        return $this->hasOne(Post::className(), ['user_id'=>'user_id'])->onCondition(['post.competition_id'=>$this->competition_id]);
        
    }*/
    

}
