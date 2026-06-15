<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\Post;
use api\modules\v1\models\CommentLike;

class Comment extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_DELETED=0;
    const TYPE_COUPON   =1;

    const LEVEL_ONE=1;
    const LEVEL_TWO=2;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'comment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','user_id','reference_id','status','created_at','type','level', 'parent_id'], 'integer'],
            [['comment'], 'string','max'=>200],
            [['reference_id','comment'], 'required', 'on'=>'create'],
            [['id'], 'required', 'on'=>'couponDelete'],
            [['reference_id'], 'required', 'on'=>'list']
            

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
            'reference_id' => Yii::t('app', 'Comment  Reference Id'),
            'created_at'=> Yii::t('app', 'created At'),
            
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
        return ['isLike','user','coupon','totalChildComment','childCommentDetail'];
    }
    
    /**
     * RELEATION START
     */
    public function getUser()
    {
       
        return $this->hasOne(User::className(), ['id'=>'user_id'])->select(['user.id', 'user.name', 'user.username', 'user.image', 'user.is_chat_user_online', 'user.chat_last_time_online', 'user.location', 'user.latitude', 'user.longitude']);
        
    }

    public function getCoupon()
    {
       
        return $this->hasOne(Coupon::className(), ['id'=>'reference_id']);
        
    }
    
    public function getChildCommentDetail()
    {
       
        return $this->hasMany(Comment::className(), ['parent_id'=>'id'])->orderBy(['id' => SORT_ASC])->limit(50);
        
    }
    public function getTotalChildComment()
    {
       
        return (int) $this->hasMany(Comment::className(), ['parent_id'=>'id'])->count();
        
    }
    public function getIsLike()
    {
        return (int) $this->hasOne(CommentLike::className(), ['comment_id'=>'id'])->andOnCondition(['comment_like.source_type' => [CommentLike::SOURCE_TYPE_COUPON] ,'comment_like.user_id' => Yii::$app->user->identity->id])->count();
    }


    
    

}
