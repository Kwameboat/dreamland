<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use common\models\PickleballTeamPlayer;
class PickleballMatchTeam extends \yii\db\ActiveRecord
{
    const WINNER_STATUS_NOT_DECLARE = 0;
    const WINNER_STATUS_WIN = 1;
    const WINNER_STATUS_LOSS = 2;

    
    
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
            'id' => 'ID',
            'name' => Yii::t('app', 'Name')
            
        ];
    }
    
    public function getTeamPlayer(){
        
        $playerStatusArr[] = PickleballTeamPlayer::STATUS_PENDING;
        $playerStatusArr[] = PickleballTeamPlayer::STATUS_ACTIVE;
        
        return $this->hasMany(PickleballTeamPlayer::className(), ['team_id' => 'id'])->where(['pickleball_team_player.status' => $playerStatusArr]);

    }
    public function updateTeamScore($teamId)
    {
        
        $modelPickleballMatchTeam = new PickleballMatchTeam();
        $resultPickleballMatchTeam = $modelPickleballMatchTeam->findOne($teamId);
        $teamScore = 0;
        foreach($resultPickleballMatchTeam->teamPlayer as $player){
            $teamScore=$teamScore+$player->point_gain;
        }
        $resultPickleballMatchTeam->team_point =  $teamScore;
        $resultPickleballMatchTeam->save();

    }

    

   



    

}
