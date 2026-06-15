<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

//use common\models\Category;

/**
 * This is the model class 
 *
 */
class BroadcastNotification extends \yii\db\ActiveRecord
{

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'broadcast_notification';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
                      
            [['id','total_user','created_at','created_by'], 'integer'],
            [['title','message_body','audience_type'], 'string']
            
            

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => Yii::t('app', 'Title'),
            'body' => Yii::t('app', 'Message Body'),
            'total_user' => Yii::t('app', 'Total User'),
            'audience_type' => Yii::t('app', 'Target audience'),
            'created_at' => Yii::t('app', 'Sent On')
            
            
            
            
        ];
    }
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->created_by = Yii::$app->user->identity->id;

        } 

        return parent::beforeSave($insert);
    }

}
