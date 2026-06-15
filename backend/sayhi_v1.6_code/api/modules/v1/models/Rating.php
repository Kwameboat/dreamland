<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
//use api\modules\v1\models\Post;

class Rating extends \yii\db\ActiveRecord
{
    const TYPE_SHOW =1;
    const STATUS_ACTIVE=10;
    const STATUS_DELETED=0;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'rating';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','user_id','type','reference_id','rating','status','created_at'], 'integer'],
            [['review'], 'string','max'=>250],
            [['type','rating'], 'required', 'on'=>'create']
        

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => Yii::t('app', 'User')
            
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

    public function extraFields()
    {
        return ['user'];
    }
    
    /**
     * RELEATION START
     */
    public function getUser()
    {
       
        return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }

    

}
