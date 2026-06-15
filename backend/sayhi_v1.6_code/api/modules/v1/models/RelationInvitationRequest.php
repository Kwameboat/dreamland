<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;

use api\modules\v1\models\User;
use api\modules\v1\models\RelationShip;
//use api\modules\v1\models\Message;

class RelationInvitationRequest extends \yii\db\ActiveRecord
{
    
    const STATUS_PENDING = 1;
    const STATUS_CANCELLED = 2;
    const STATUS_REJECTED = 3;
    const STATUS_ACCEPTED=4;
    const STATUS_DELETED = 5;


    public $user_ids;
    
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'relation_invitation_request';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        
        return [
            [['id','relation_ship_id','user_id','status','created_at','created_by'], 'integer'],
            [[ 'relation_ship_id','user_id' ], 'required','on'=>['invite']],
            [[ 'id','status' ], 'required','on'=>['updateInvitation']],
               
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
            $this->created_by  =   Yii::$app->user->identity->id;
        }
        
        return parent::beforeSave($insert);
    }
    
    public function extraFields()
    {
        return ['user','relationShip','createdBy'];
    }

    public function getUser()
    {
       return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }

    public function getCreatedBy()
    {
       return $this->hasOne(User::className(), ['id'=>'created_by']);
        
    }

    public function getRelationShip()
    {
       return $this->hasOne(RelationShip::className(), ['id'=>'relation_ship_id']);
        
    }

}
