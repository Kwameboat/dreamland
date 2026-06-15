<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\User;
use api\modules\v1\models\PickleballMatch;
use api\modules\v1\models\PickleballMatchTeam;




class PickleballTeamPlayer extends \yii\db\ActiveRecord
{
    
    const STATUS_DELETED = 0;
    const STATUS_PENDING = 1;
    const STATUS_REJECTED = 2;
    const STATUS_ACCEPTED = 3; // virtually to use, not saved
    const STATUS_BLOCKED=9;
    const STATUS_ACTIVE = 10;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pickleball_team_player';
    }

    

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','match_id','team_id','player_id', 'point_gain','status','created_at'], 'integer'],
            [['id','status' ], 'required','on'=>'replyInvitation'],
            [['player_id','team_id'], 'required','on'=>'addPlayer'],
            [['id'], 'required','on'=>'removePlayer'],
            
            
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
        }
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
        return ['playerDetail'];
    }
    
    public function getPickleballMatch()
    {
        return $this->hasOne(PickleballMatch::className(), ['id'=>'match_id']);
        
    }
    
    public function getPlayerDetail()
    {
        return $this->hasOne(User::className(), ['id'=>'player_id']);
        
    }
    public function getPickleballMatchTeam()
    {
        return $this->hasOne(PickleballMatchTeam::className(), ['id'=>'team_id']);
        
    }
    
    
    
   
    
}
