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
class City extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=1;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'city';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            
            [['state_id', 'id'], 'integer'],
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
            'name' => Yii::t('app','Name')
            
        ];
    }

    public function getCountryDropdown(){
        $countries = $this->find()->select(['id','name'])->where(['status'=>$this::STATUS_ACTIVE])->all();
       return ArrayHelper::map($countries,'id','name');
        
    }

    public function getCityList($state_id){
        return  $this->find()->select(['id','name'])->where(['state_id'=>$state_id])->all();
       
        
    }

    
    public function getCityById($id){
        return  $this->find()->select(['id','name'])->where(['id'=>$id])->one();
       
        
    }


}
