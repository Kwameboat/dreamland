<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\Story;


class HighlightStory extends \yii\db\ActiveRecord
{
    
    public $story_ids;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'highlight_story';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','highlight_id','story_id','created_at'], 'integer'],
           // [['name', 'status'], 'save'],
             [['highlight_id','story_ids'], 'required','on'=>['create']],
             [['id'], 'required','on'=>['delete']],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID'
            
            
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
         
          
        }
        return parent::beforeSave($insert);
    }

    
    public function fields()
    {
        
        $fields = parent::fields();
       // unset($fields['parent_id'],$fields['status'],$fields['priority'],$fields['leval']);
        $fields[] = 'story';
        return $fields;
    }


    public function addStory($highlightId,$storyIds){
        $values=[];
        $stories = explode(',',$storyIds);
        foreach($stories as $storyId){
          $storyId=(int)$storyId;
            if($storyId>0){
                $locationValue['highlight_id']           =   $highlightId;
                $locationValue['story_id']        =   (int)$storyId;
                $locationValue['created_at']        =   time();
                $values[]=$locationValue;
            }
        }   
        if(count($values)>0){
            Yii::$app->db
            ->createCommand()
            ->batchInsert('highlight_story', ['highlight_id','story_id','created_at'],$values)
            ->execute();
        }
    }


    
    public function getStory()
    {
        return $this->hasOne(Story::className(), ['id'=>'story_id']);
        
    }


    

}
