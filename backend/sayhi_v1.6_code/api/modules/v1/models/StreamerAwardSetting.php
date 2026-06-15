<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;


class StreamerAwardSetting extends \yii\db\ActiveRecord
{

    const STATUS_ACTIVE=10;
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
            [['id','position_id','status'],'integer'],
            [['award_coin'], 'number'],
            
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            
        ];
    }
    public function fields()
    {
        $fields = parent::fields();
     
        


        return $fields;
    }

    public function getEnableDisableDropDownData()
    {
        return array(1 => 'Enable', 0 => 'Disable');
    }
    
    public function getAwardSetting()
    {
        return $this->find()->where(['status'=>StreamerAwardSetting::STATUS_ACTIVE])->orderBy(['position_id'=>SORT_DESC])->asArray()->all();
    }
   

    
    

}
