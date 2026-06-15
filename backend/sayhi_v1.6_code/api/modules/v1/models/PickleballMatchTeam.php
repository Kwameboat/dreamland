<?php
namespace api\modules\v1\models;
use Yii;


use api\modules\v1\models\PickleballTeamPlayer;
use api\modules\v1\models\PickleballMatch;


class PickleballMatchTeam extends \yii\db\ActiveRecord
{
    
        
    const WINNER_STATUS_NOT_DECLARE = 0;
    const WINNER_STATUS_NOT_WIN = 1;
    const WINNER_STATUS_NOT_LOSS = 2;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pickleball_match_team';
    }

    

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[ 'name'], 'string'],
            [['id','match_id','winner_status', 'team_point'], 'integer'],
            
            
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
       // $fields[] = 'imageUrl';
        return $fields;
    }
    
    public function extraFields()
    {
        return ['teamPlayer'];
    }
    public function getImageUrl()
    {
        if($this->image){
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_PICKLEBALL_COURT ,$this->image);
           
        }else{
            return '';
        }
        
    }

    public function getTeamPlayer(){
        
        $playerStatusArr[] = PickleballTeamPlayer::STATUS_PENDING;
        $playerStatusArr[] = PickleballTeamPlayer::STATUS_ACTIVE;
        
        return $this->hasMany(PickleballTeamPlayer::className(), ['team_id' => 'id'])->where(['pickleball_team_player.status' => $playerStatusArr]);

    }
    public function getPickleballMatch()
    {
        return $this->hasOne(PickleballMatch::className(), ['id'=>'match_id']);
        
    }
    

    
    
   
    
}
