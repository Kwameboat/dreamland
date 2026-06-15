<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "countryy".
 *
 * @property int $id
 * @property string $name
 * @property int $status
 * @property int $created_at
 */
class State extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=1;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'state';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            
            [['country_id','status', 'id'], 'integer'],
            [['name'], 'string', 'max' => 256],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app','ID'),
            'name' => Yii::t('app','Name'),
            'status' => Yii::t('app','Status'),
            'country_id' => Yii::t('app','Country'),
        ];
    }

    public function getCountryDropdown(){
        $data = $this->find()->select(['id','name'])->where(['status'=>$this::STATUS_ACTIVE])->all();
       return ArrayHelper::map($data,'id','name');
        
    }

    public function getStateList($country_id){
        return  $this->find()->select(['id','name'])->where(['country_id'=>$country_id])->all();
       
        
    }

    public function getStateById($stateId){
        return  $this->find()->select(['id','name'])->where(['id'=>$stateId])->one();
       
        
    }


}
