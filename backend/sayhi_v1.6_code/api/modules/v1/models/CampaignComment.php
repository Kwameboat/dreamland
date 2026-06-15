<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\Campaign;





class CampaignComment extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_DELETED=0;

    const LEVEL_ONE=1;
    const LEVEL_TWO=2;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'campaign_comment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','user_id','campaign_id','status','created_at','level','parent_id'], 'integer'],
            [['comment'], 'string','max'=>200],
            [['campaign_id','comment'], 'required', 'on'=>'create'],
            [['campaign_id'], 'required', 'on'=>'list'],
            [['id'], 'required', 'on'=>'commentdelete'],

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
        return ['isLike','user','campaign','totalChildComment','childCommentDetail'];
    }
    
    /**
     * RELEATION START
     */
    public function getUser()
    {
       
        return $this->hasOne(User::className(), ['id'=>'user_id'])->select(['user.id', 'user.name', 'user.username', 'user.image', 'user.is_chat_user_online', 'user.chat_last_time_online', 'user.location', 'user.latitude', 'user.longitude']);
        
    }

    public function getCampaign()
    {
       
        return $this->hasOne(Campaign::className(), ['id'=>'campaign_id']);
        
    }

    public function getChildCommentDetail()
    {
       
        return $this->hasMany(CampaignComment::className(), ['parent_id'=>'id'])->orderBy(['id' => SORT_ASC])->limit(50);
        
    }
    public function getTotalChildComment()
    {
       
        return (int) $this->hasMany(CampaignComment::className(), ['parent_id'=>'id'])->count();
        
    }
    public function getIsLike()
    {
        return (int) $this->hasOne(CommentLike::className(), ['comment_id'=>'id'])->andOnCondition(['comment_like.source_type' => CommentLike::SOURCE_TYPE_CAMPAIGN  ,'comment_like.user_id' => Yii::$app->user->identity->id])->count();
    }


    


    
    

}
