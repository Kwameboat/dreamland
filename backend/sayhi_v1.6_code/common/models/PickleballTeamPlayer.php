<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use common\models\User;
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
    public function getPlayerDetail()
    {
        return $this->hasOne(User::className(), ['id'=>'player_id']);
        
    }
    

   



    

}
