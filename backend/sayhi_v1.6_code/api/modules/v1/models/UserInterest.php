<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\Interest;

class UserInterest extends \yii\db\ActiveRecord
{
    const STATUS_DELETED = 0;
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;
    public $counter;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_interest';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['interest_id','status','user_id','name'], 'required'],
            [['interest_id','status','user_id'], 'integer'],
            
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app','ID'),
            'interest_id' => Yii::t('app','Interest'),
            'status' => Yii::t('app','Status'),
            'user_id' => Yii::t('app','User')
            
        ];
    }

    public function fields()
    {
        $fields = parent::fields();
  
        $fields['name'] = (function($model){
            return $model->interestDetailName->name;
        });
        return $fields;
    }

    public function extraFields()
    {
        return ['interestDetailName'];
    }
    

    public function updateUserInterest($userId,$interest){

        $interestData = explode(',',$interest);
        $values=[];
        
        foreach($interestData as $data){
                   
            $userValue['user_id']           =   $userId;
            $userValue['interest_id']       =   $data;
            $userValue['status']       =   SELF::STATUS_ACTIVE;
            $userValue['created_at']        = strtotime('now');
            $values[]=$userValue;

        }   

        if(count($values)>0){

            Yii::$app
            ->db
            ->createCommand()
            ->delete('user_interest', ['user_id' => $userId])
            ->execute();

            Yii::$app->db
            ->createCommand()
            ->batchInsert('user_interest', ['user_id','interest_id','status','created_at'],$values)
            ->execute();
        }
    }
    
    public function getInterestDetailName()
    {
        return $this->hasOne(Interest::className(), ['id'=>'interest_id'])->select('id,name');
    }


}
