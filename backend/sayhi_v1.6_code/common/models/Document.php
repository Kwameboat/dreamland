<?php
namespace common\models;
use Yii;
 


/**
 * This is the model class 
 *
 */
class Document extends \yii\db\ActiveRecord
{
    

  
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'documents';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['profile_id'], 'required'],
            [['driving_license','registration_certificate','vehicle_insurance','vehicle_permit','createdAt','updatedAt'], 'string'],
           ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'profile_id' => Yii::t('app', 'Profile Id'),
            'Document Status' => Yii::t('app', 'Document Status'),
            'dl_card_number' => Yii::t('app', 'DL Card Number'),
            'dl_expiry_date' => Yii::t('app', 'DL Exp Date'),
            'is_approved' => Yii::t('app', 'Is Approved'),
            'createdAt' => Yii::t('app', 'Create Date'),
            'updatedAt' => Yii::t('app', 'Update Date'), 
        ];
    }
}
