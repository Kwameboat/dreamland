<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use common\models\User;


class BlockedUser extends \yii\db\ActiveRecord
{
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'blocked_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','user_id','blocked_user_id','created_at'], 'integer'],
            [['blocked_user_id'], 'required', 'on'=>['create','unBlock']],
            
            //[['user_ids'], 'required', 'on'=>'createMultiple']

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
           
            
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

    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
       /// unset($fields['status'], $fields['template_type'], $fields['category'], $fields['created_at'], $fields['updated_at'], $fields['created_by'], $fields['updated_by']);
        //$fields[] = 'picture';
        //$fields[] = 'userLocation';
        return $fields;
    }

    public function getUserIdsWhomeBlockMe($userId)
    {
        $model = new BlockedUser();
        $userIds=[];
        $results = $model->find()
         ->where(
            [
                'or',

                ['blocked_user_id'=>$userId],
                ['user_id' => $userId],

            ]
        )
        ->asArray()->all();
        foreach($results as $record){
            if($record['user_id']!=$userId){
                $userIds[]=$record['user_id'];
            }
            if($record['blocked_user_id']!=$userId){
                $userIds[]=$record['blocked_user_id'];
            }
         }
        return $userIds;

    }
    

    


    public function getFollowingUserDetail()
    {
        return $this->hasOne(User::className(), ['id'=>'user_id']);
    }


   public function getBlockedUserDetail()
    {
        
           return $this->hasOne(User::className(), ['id'=>'blocked_user_id']);
    }

   


}
