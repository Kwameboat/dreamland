<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;

use api\modules\v1\models\User;
use api\modules\v1\models\Club;
//use api\modules\v1\models\Message;

class ClubInvitationRequest extends \yii\db\ActiveRecord
{
    
    const STATUS_PENDING = 1;
    const STATUS_CANCELLED = 2;
    const STATUS_REJECTED = 3;
    const STATUS_ACCEPTED=10;


    const TYPE_INVITATION=1;
    const TYPE_REQUEST=2;

    public $user_ids;
    
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'club_invitation_request';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        
        return [
            [['id','club_id','user_id','type','status','created_at','created_by'], 'integer'],
            [['message','user_ids'], 'string'],
            [[ 'club_id','user_ids' ], 'required','on'=>['invite']],
            [[ 'id','status' ], 'required','on'=>['invitationReply','jointRequestReply']],
            [[ 'club_id'], 'required','on'=>['joinRequest']],
            
            
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
        return ['user','club'];
    }




    public function getUser()
    {
       return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }

    public function getClub()
    {
       return $this->hasOne(Club::className(), ['id'=>'club_id']);
        
    }


}
