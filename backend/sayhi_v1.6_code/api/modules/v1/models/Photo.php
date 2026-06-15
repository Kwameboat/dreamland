<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\Category;
use yii\helpers\Url;
//use common\models\Category;

class Photo extends \yii\db\ActiveRecord
//class Photo extends \yii\db\BaseActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;
    public $imageFile;
    
    
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'photo';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'status','category','template_type'], 'required'],
            [['imageFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'zip'],
            [['status', 'id'], 'integer'],
            [['title'], 'string', 'max' => 100],
            
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
            'title' => Yii::t('app', 'Title'),
            'status' => Yii::t('app', 'Status'),
            
            
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->created_by =   Yii::$app->user->identity->id;
          
        }else{

           
            $this->updated_at = time();
            $this->updated_by =   Yii::$app->user->identity->id;

        }
        return parent::beforeSave($insert);
    }

    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['status'], $fields['template_type'], $fields['category'], $fields['created_at'], $fields['updated_at'], $fields['created_by'], $fields['updated_by']);
       $fields[] = 'categoryDetail';
       $fields[] = 'templateString';
       $fields[] = 'imageUrl';
       $fields[] = 'createdDate';
       $fields[] = 'updatedDate';

       

        return $fields;
    }

    /*public function fields()
    {
        return [
            // field name is the same as the attribute name
            'id',
            'title',
            // field name is "email", the corresponding attribute name is "email_address"
            //'title' => 'email_address',
            // field name is "name", its value is defined by a PHP callback
            'image' => function ($model) {
                return $model->id . ' ' . $model->image;
            },
        ];
    }*/

   /* public function fields()
    {
        return ['id', 'title'];
    }
    */

   
    
   
    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_INACTIVE => 'Inactive');
    }


    public function getTemplateDropDownData()
    {
        return array(1 => 'tmaplate1', 2 =>'template2');
    }

    public function getTemplateString()
    {
        $templatges = $this->getTemplateDropDownData();
        return $templatges[$this->template_type];
    }

    public function getStatus()
    {
       if($this->status==$this::STATUS_INACTIVE){
           return 'Inactive';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }
    }

    public function getCategoryDetail()
    {
        return $this->hasOne(Category::className(), ['id'=>'category'])->select('name');
        
    }


    public function getImageUrl()
    {
   
      return  Yii::getAlias('@siteUrl').Yii::$app->urlManagerFrontend->baseUrl.'/uploads/photo/'. $this->image;
   
        
    }

    public function getCreatedDate()
    {
   
      return  Yii::$app->formatter->asDate($this->created_at, 'yyyy/MM/dd'); // 2014-10-06
   
        
    }

    
    public function getUpdatedDate()
    {
   
      if($this->updated_at){
        return  Yii::$app->formatter->asDate($this->updated_at, 'yyyy/MM/dd'); // 2014-10-06
      }else{
          return null;
      }
        
   
        
    }


    

}
