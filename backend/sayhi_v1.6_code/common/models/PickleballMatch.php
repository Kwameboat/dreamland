<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use common\models\PickleballCourt;
use common\models\PickleballMatchTeam;

class PickleballMatch extends \yii\db\ActiveRecord
{
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_CANCELLED = 2;
    const STATUS_BLOCKED=9;
    const STATUS_COMPLETED=10;


    const MATCH_TYPE_SINGLE=1;
    const MATCH_TYPE_DOUBLE=2;

    
    
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
            //[['match_type','court_id','point_to_win' ], 'required','on'=>'create'],
            //[['id','match_team','winner_team_id' ], 'required','on'=>'declareResult'],
            //[['match_team'],'safe']

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => Yii::t('app', 'Name'),
            'status' => Yii::t('app', 'Status'),
            'court_id'=> Yii::t('app', 'Court'),
        ];
    }
    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_INACTIVE => 'Inactive');
    }
   
    public function getStatus()
    {
        if($this->status==$this::STATUS_ACTIVE){
            return 'Active';    
       }else if($this->status==$this::STATUS_CANCELLED){
        return 'Cancelled';    
        }else if($this->status==$this::STATUS_BLOCKED){
            return 'Blocked';    
        }else if($this->status==$this::STATUS_COMPLETED){
            return 'Completed';    
        }
       
    }
    public function getType()
    {
       if($this->match_type==$this::MATCH_TYPE_SINGLE){
           return 'Single';
       }else if($this->match_type==$this::MATCH_TYPE_DOUBLE){
           return 'Double';    
       }
    }
   
    public function getCourt(){
        
        return $this->hasOne(PickleballCourt::className(), ['id' => 'court_id']);

    }
    public function getMatchTeam(){
        
        return $this->hasMany(PickleballMatchTeam::className(), ['match_id' => 'id']);

    }

   



    

}
