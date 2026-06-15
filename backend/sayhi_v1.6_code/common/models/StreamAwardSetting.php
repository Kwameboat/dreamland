<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use common\models\Category;
use api\modules\v1\models\User;


/**
 * This is the model class 
 *
 */
class StreamAwardSetting extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'streamer_award_setting';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['position_id', 'award_coin','status'], 'required'],
            
            [['status', 'id','position_id','created_by','created_by'], 'integer'],
            [['award_coin'], 'safe'],
            [['position_id','award_coin'], 'required','on'=>['create','update']],
            

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'position_id' => Yii::t('app', 'Position'),
            'status' => Yii::t('app', 'Status'),
            'award_coin' => Yii::t('app', 'Award Coin'),
            
        ];
    }



    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_INACTIVE => 'Inactive');
    }

    public function getStatus()
    {
       if($this->status==$this::STATUS_INACTIVE){
           return 'Inactive';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }
    }
  
    public function getPositionDropDownData()
    {
        return array(
            "1" => '1th Position',
            "2" => '2th Position',
            "3" => '3th Position',
            "4" => '4th Position',
            "5" => '5th Position',
            "6" => '6th Position',
            "7" => '7th Position',
            "8" => '8th Position',
            "9" => '9th Position',
            "10" => '10th Position'
        );
    }

    public function getPosition()
    {
        if($this->position_id==1){
            return '1th Position';
        }else if($this->position_id==2){
            return '2th Position';    
        }else if($this->position_id==3){
            return '3th Position';    
        }else if($this->position_id==4){
            return '4th Position';    
        }else if($this->position_id==5){
            return '5th Position';    
        }else if($this->position_id==6){
            return '6th Position';    
        }else if($this->position_id==7){
            return '7th Position';    
        }else if($this->position_id==8){
            return '8th Position';    
        }else if($this->position_id==9){
            return '9th Position';    
        }else if($this->position_id==10){
            return '10th Position';    
        }else if($this->position_id==11){
            return '11th Position';    
        }
        
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->created_by   =   Yii::$app->user->identity->id;
            
        }

        
        return parent::beforeSave($insert);
    }

}
