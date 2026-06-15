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
class DriverDocument extends \yii\db\ActiveRecord
{




    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'driverprofiles';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['dl_card_number', 'dl_expiry_date', 'is_dl_approved', 'is_rc_approved', 'is_vi_approved', 'is_vp_approved'], 'integer'],
            [['createdAt', 'updatedAt'], 'string'],
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
            'dl_card_number' => Yii::t('app', 'DL Card Number'),
            'dl_expiry_date' => Yii::t('app', 'DL Exp Date'),
            'createdAt' => Yii::t('app', 'Create Date'),
            'updatedAt' => Yii::t('app', 'Update Date'),
        ];
    }
    public function extraFields()
    {
        return ['dlImageUrl','rcImageUrl','viImageUrl','vpImageUrl'];
    }
    public function getDlImageUrl()
    {
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_CAR_RIDE, $this->documents->driving_license);
    }

    public function getRcImageUrl()
    {
     
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_CAR_RIDE, $this->documents->registration_certificate);
    }
    public function getViImageUrl()
    {
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_CAR_RIDE, $this->documents->vehicle_insurance);
    }

    public function getVpImageUrl()
    {
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_CAR_RIDE, $this->documents->vehicle_permit);
    }
   
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public function getDocuments()
    {
        return $this->hasOne(Document::className(), ['profile_id' => 'id']);
    }
    public function getIsDlApprovedDropDownData()
    {
        return array(0 => 'No', 1 => 'Yes');
    }
    public function getIsRcApprovedDropDownData()
    {
        return array(0 => 'No', 1 => 'Yes');
    }

    public function getIsViApprovedDropDownData()
    {
        return array(0 => 'No', 1 => 'Yes');
    }
    public function getIsVpApprovedDropDownData()
    {
        return array(0 => 'No', 1 => 'Yes');
    }
}
