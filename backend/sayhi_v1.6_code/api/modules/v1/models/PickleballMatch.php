<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\PickleballCourt;
use api\modules\v1\models\PickleballMatchTeam;
use api\modules\v1\models\PickleballTeamPlayer;


class PickleballMatch extends \yii\db\ActiveRecord
{
    
    
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_CANCELLED = 2;
    const STATUS_BLOCKED=9;
    const STATUS_COMPLETED=10;


    const MATCH_TYPE_SINGLE=1;
    const MATCH_TYPE_DOUBLE=2;

    public $match_team;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pickleball_match';
    }

    

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            
            [['id','match_type','court_id','start_time','point_to_win','winner_team_id','result_declared_at','status', 'created_at','created_by','updated_at','updated_by'], 'integer'],
            ['match_type', 'in', 'range' => [1,2]],
            [['match_type','court_id','point_to_win' ], 'required','on'=>'create'],
            [['id','match_team','winner_team_id' ], 'required','on'=>'declareResult'],
            [['match_team'],'safe']
            
            
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
            $this->created_by  =   Yii::$app->user->identity->id;
           
        }
        return parent::beforeSave($insert);
    }

    public function fields()
    {
        $fields = parent::fields();
        //$fields[] = 'imageUrl';
        return $fields;
    }
    
    public function extraFields()
    {
        return ['court','matchTeam'];
    }
    public function getCourt(){
        
        return $this->hasOne(PickleballCourt::className(), ['id' => 'court_id']);

    }
    public function getMatchTeam(){
        
        return $this->hasMany(PickleballMatchTeam::className(), ['match_id' => 'id']);

    }
    public function getTeamPlayer(){
        
        return $this->hasMany(PickleballTeamPlayer::className(), ['match_id' => 'id']);

    }


    
   
    
   
    
}
