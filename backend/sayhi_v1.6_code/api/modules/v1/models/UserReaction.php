<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\User;

class UserReaction extends \yii\db\ActiveRecord
{
    const TYPE_EVENT=1;    

    const REACTION_INTERESTING=1;    
    const REACTION_NOT_INTERESTING=2;    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_reaction';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','user_id','type','reference_id','reaction','created_at','updated_at','total_item'], 'integer'],
            [['type','reference_id','reaction'], 'required', 'on'=>'create']
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
            'created_at'=> Yii::t('app', 'Reported At'),
            
        ];
    }
   
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->user_id =   ($this->user_id>0) ? $this->user_id : Yii::$app->user->identity->id;
          
        }else{
            $this->updated_at = time();
        }
        return parent::beforeSave($insert);
    }
    public function extraFields()
    {
        return ['user'];
    }
    

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id'=>'user_id']);
    }

}
