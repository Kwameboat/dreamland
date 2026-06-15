<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use common\models\Post;


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
    /*
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
           
        } else {
            $this->updated_at = time();
           
        }

        return parent::beforeSave($insert);
    }
    */

   
  
    public function updateCompetitionPosition($inputPosition){

     
        
        $competitionId              =   $inputPosition['competitionId'];
        $competitionPositionArr     =   $inputPosition['competitionPosition'];   
        $competitionAwardArr        =   $inputPosition['competitionAward'];
        CompetitionPosition::deleteAll(['competition_id'=>$competitionId]);
        $values=[];
        $i=0;

        

        if(count($competitionPositionArr)>1){
            array_pop($competitionPositionArr);
        }
        
        
        
        foreach($competitionPositionArr as $competitionPosition){
            //  print_r($location);
              $dataInner['competition_id']             = $competitionId;
              $dataInner['title']                     =  $competitionPosition;
              $dataInner['award_value']      =               $competitionAwardArr[$i];


              $values[]=$dataInner;
              
              $i++;
  
          }   
  
          if(count($values)>0){
  
             
  
              Yii::$app->db
              ->createCommand()
              ->batchInsert('competition_winner_position', ['competition_id','title','award_value'],$values)
              ->execute();
          }


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
