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
class Country extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=1;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'country';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'sortname'], 'required'],
            [['status', 'id'], 'integer'],
            [['name'], 'string', 'max' => 256],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'status' => 'Status',
            'sortname' => 'Sortname',
        ];
    }

    public function getCountryDropdown(){
        $countries = $this->find()->select(['id','name'])->where(['status'=>$this::STATUS_ACTIVE])->orderBy(['name'=>SORT_ASC])->all();
       return ArrayHelper::map($countries,'id','name');
        
    }

    public function getCountryList(){
        return  $this->find()->select(['id','name'])->where(['status'=>$this::STATUS_ACTIVE])->all();
       
        
    }
}
