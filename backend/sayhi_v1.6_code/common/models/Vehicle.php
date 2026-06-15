<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use common\models\LiveTvCategory;


/**
 * This is the model class 
 *
 */
class Vehicle extends \yii\db\ActiveRecord
{
    

  
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'vehicles';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['year'], 'integer'],
            [['car_number','vehicle_brand','model','color','booking_type','createdAt','updatedAt'], 'string'],
           ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => Yii::t('app', 'User Id'),
            'car_number' => Yii::t('app', 'Car Number'),
            'vehicle_brand' => Yii::t('app', 'Vehicle Brand'),
            'model' => Yii::t('app', 'Model'),
            'year' => Yii::t('app', 'Year'),
            'color' => Yii::t('app', 'Color'),
            'booking_type' => Yii::t('app', 'Booking Type'),
            'createdAt' => Yii::t('app', 'Create Date'),
            'updatedAt' => Yii::t('app', 'Update Date'), 
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }


    

}
