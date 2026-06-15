<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\User;
use api\modules\v1\models\PickleballMatch;
use api\modules\v1\models\PickleballMatchTeam;
use api\modules\v1\models\Post;




class Collaborate extends \yii\db\ActiveRecord
{
    
    const STATUS_DELETED = 0;
    const STATUS_PENDING = 1;
    const STATUS_REJECTED = 2;
    const STATUS_ACCEPTED = 3; // virtually to use, not saved
    const STATUS_CANCELLED = 4; 
    const STATUS_ACTIVE = 10;

    const TYPE_POST = 1;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'collaborate';
    }

    

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','reference_id','type','collaborator_id','created_at','status','author_id'], 'integer'],
            [['collaborator_id','reference_id','type'], 'required','on'=>'add'],
            [['id','status' ], 'required','on'=>'replyInvitation'],
            [['id'], 'required','on'=>'remove'],
            
            
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
        }
        return parent::beforeSave($insert);
    }

    public function fields()
    {
        $fields = parent::fields();
       // $fields[] = 'imageUrl';
        return $fields;
    }
    
    public function extraFields()
    {
        return ['collaboratorDetail','post'];
    }
    
    public function getCollaboratorDetail()
    {
        return $this->hasOne(User::className(), ['id'=>'collaborator_id']);
    }
    public function getPost()
    {
        return $this->hasOne(Post::className(), ['id'=>'reference_id']);
    }
   
    
}
