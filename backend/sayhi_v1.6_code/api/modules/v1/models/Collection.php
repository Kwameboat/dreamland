<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\CollectionUser;


class Collection extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'collection';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'id','user_id','created_at'], 'integer'],
            [['image'], 'string'],
            [['name'], 'string', 'max' => 100],
            [['name'], 'required','on'=>['create','update']],
            
           // [['name', 'status'], 'save'],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => Yii::t('app', 'Name'),
            'status' => Yii::t('app', 'Status')
           
            
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->user_id       =   Yii::$app->user->identity->id;
          
        }
        return parent::beforeSave($insert);
    }

    
    public function fields()
    {
        
        $fields = parent::fields();
       // unset($fields['parent_id'],$fields['status'],$fields['priority'],$fields['leval']);
       $fields[] = "imageUrl"; 
       $fields[] = 'collectionUser';
        return $fields;
    }


    public function getCollectionUser()
    {
        return $this->hasMany(CollectionUser::className(), ['collection_id'=>'id']);
        
    }
 


   /* public function extraFields()
    {
        return ['subCategory'];
    }
   
    public function getMainCategory(){
        return $this->find()->select(['id','name'])->where(['status'=>$this::STATUS_ACTIVE,'level'=>$this::LEVEL_MAIN])->all();
        
    }
    */    

    public function getImageUrl(){
        if($this->image){
            return Yii::$app->params['pathUploadCollection'] ."/".$this->image;
        }
     }

    

}
