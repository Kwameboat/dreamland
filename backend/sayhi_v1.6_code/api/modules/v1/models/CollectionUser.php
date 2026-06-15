<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\Post;


class CollectionUser extends \yii\db\ActiveRecord
{
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'collection_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','collection_id','post_id','created_at'], 'integer'],
           // [['name', 'status'], 'save'],
           [['collection_id','post_id'], 'required','on'=>['create']],
           [['id'], 'required','on'=>['delete']],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID'
            
            
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
       // unset($fields['parent_id'],$fields['status'],$fields['priority'],$fields['leval']);
        $fields[] = 'post';
        return $fields;
    }

    
    public function getPost()
    {
        return $this->hasOne(Post::className(), ['id'=>'post_id']);
        
    }


    

}
