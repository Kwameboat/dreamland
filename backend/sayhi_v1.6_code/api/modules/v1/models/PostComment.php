<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\Post;
use api\modules\v1\models\CommentLike;
use api\modules\v1\models\Pin;

class PostComment extends \yii\db\ActiveRecord
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
        return 'post_comment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','type','user_id','post_id','status','created_at','level','parent_id'], 'integer'],
            [['comment','filename'], 'string','max'=>255],
            [['post_id','type'], 'required', 'on'=>'create'],
            [['post_id'], 'required', 'on'=>'list']
            

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
            'post_id' => Yii::t('app', 'Ad'),
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
    
    public function fields()
    {
        $fields = parent::fields();
        $fields[] = "filenameUrl";
        return $fields;
    }

    public function extraFields()
    {
        return ['isLike','isPin','user','post','totalChildComment','childCommentDetail'];
    }
    
    /**
     * RELEATION START
     */
    public function getUser()
    {
       
        return $this->hasOne(User::className(), ['id'=>'user_id'])->select(['user.id', 'user.name', 'user.username', 'user.image', 'user.is_chat_user_online', 'user.chat_last_time_online', 'user.location', 'user.latitude', 'user.longitude']);
        
    }

    public function getPost()
    {
       
        return $this->hasOne(Post::className(), ['id'=>'post_id']);
        
    }

    public function getChildCommentDetail()
    {
       
        return $this->hasMany(PostComment::className(), ['parent_id'=>'id'])->orderBy(['id' => SORT_ASC])->limit(50);
        
    }
    public function getTotalChildComment()
    {
       
        return (int) $this->hasMany(PostComment::className(), ['parent_id'=>'id'])->count();
        
    }
    
    public function getFilenameUrl(){

        if($this->filename && $this->type !=1 ){ /// not text
            if($this->type==4){ /// if gif
                return $this->filename;
            }else{
                
                return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_POST,$this->filename);
            }
        }
     }



     public function getIsLike()
     {
         return (int) $this->hasOne(CommentLike::className(), ['comment_id'=>'id'])->andOnCondition(['comment_like.source_type' => CommentLike::SOURCE_TYPE_POST ,'comment_like.user_id' => Yii::$app->user->identity->id])->count();
     }
     public function getIsPin()
     {
         return $this->hasOne(Pin::className(), ['reference_id'=>'id'])->andOnCondition(['pin.user_id' => Yii::$app->user->identity->id,'pin.type'=>Pin::TYPE_COMMENT]);
         
     }

    


    
    

}
