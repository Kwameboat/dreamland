<?php
namespace api\modules\v1\models;

use api\modules\v1\models\CompetitionUser;
use api\modules\v1\models\Post;
use api\modules\v1\models\CompetitionExampleImage;
use Yii;

class CompetitionPosition extends \yii\db\ActiveRecord
{
  
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'competition_winner_position';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','competition_id','winner_user_id','winner_post_id','awarded_at'], 'integer'],
            [['award_value'], 'number'],
           [['title',], 'string', 'max' => 200]

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
       

        return parent::beforeSave($insert);
    }

    public function fields()
    {
        $fields = parent::fields();
        /*$fields[] = 'imageUrl';
        $fields['expampleImages'] = (function ($model) {
            $imageArr = [];
            foreach ($model->expampleImages as $img) {
                $imageArr[] = $img->imageUrl;
            }
            return $imageArr;
        });
        $fields['is_joined'] = (function($model){
            return (@$model->isJoined) ? 1: 0;
        });*/
       
       // $fields[] = 'competitionImage';
        return $fields;
    }

    public function extraFields()
    {
        return ['post'];
    }
    
  
    /**
     * RELEATION START
     */

   
 
    public function getUserDetail()
    {
        return $this->hasOne(User::className(), ['id'=>'winner_user_id']);
        
    }
    public function getPost()
    {
        return $this->hasOne(Post::className(), ['id'=>'winner_post_id']);
        
    }


}
