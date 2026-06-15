<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\Ad;

class FavoriteAd extends \yii\db\ActiveRecord
{
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'favorite_ad';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','user_id','ad_id','created_at'], 'integer'],
            [['ad_id'], 'required', 'on'=>'create']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => Yii::t('app', 'User'),
            'ad' => Yii::t('app', 'Ad'),
            'created_at'=> Yii::t('app', 'Reported At'),
            
        ];
    }
   
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->user_id =   Yii::$app->user->identity->id;
          
        }

        
        return parent::beforeSave($insert);
    }
    


    public function getAd()
    {
        return $this->hasOne(Ad::className(), ['id'=>'ad_id']);
        
    }
    

    

}
