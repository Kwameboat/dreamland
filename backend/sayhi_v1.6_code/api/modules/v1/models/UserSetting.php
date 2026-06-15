<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;

class UserSetting extends \yii\db\ActiveRecord
{
    public $user_ids;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_setting';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        
        return [
            [['id','user_id','relation_setting','created_at'], 'integer'],
            [['relation_setting'], 'required','on'=>['add_setting']],       
        ];
    }
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->user_id  =   Yii::$app->user->identity->id;
        }
        
        return parent::beforeSave($insert);
    }
}
