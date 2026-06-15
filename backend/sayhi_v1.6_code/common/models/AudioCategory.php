<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use frontend\models\Ad;

/**
 * This is the model class for table "countryy".
 *
 */
class Category extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    const LEVEL_MAIN = 1;
    const LEVEL_SUB = 2;

    public $imageFile;

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'category';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'status'], 'required'],
            [['parent_id'], 'required','on'=>['createSubCategory','updateSubCategory']],
            [['imageFile'], 'file', 'skipOnEmpty' => true],
            [['status', 'id','parent_id','priority'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['imageFile'], 'required','on'=>'createMainCategory'],
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
            'status' => Yii::t('app', 'Status'),
            'parent_id' => Yii::t('app', 'Main Category'),
            
        ];
    }
    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_INACTIVE => 'Inactive');
    }

    public function getStatus()
    {
       if($this->status==$this::STATUS_INACTIVE){
           return 'Inactive';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }
    }
   public function upload()
    {
        
        if ($this->validate()) {
            if($this->imageFile){

            $filename=$this->imageFile->baseName.'_'.time(). '.' . $this->imageFile->extension;
            $this->imageFile->saveAs('@frontend/web/uploads/category/' .$filename ,false);
            return $filename;
            }
        } else {
            return false;
        }
    }
    
    public function getMainCategory(){
        return $this->find()->select(['id','name','image'])->where(['status'=>$this::STATUS_ACTIVE,'level'=>$this::LEVEL_MAIN])->all();
        
    }
    public function getSubCategory($parentId){
        return $this->find()->select(['id','name'])->where(['status'=>$this::STATUS_ACTIVE,'level'=>$this::LEVEL_SUB,'parent_id'=>$parentId])->all();
        
    }
    public function getParent(){

        return $this->hasOne(Category::className(), ['id' => 'parent_id']);

    }

    
    public function getChildCategory(){

        return $this->hasMany(Category::className(), ['parent_id' => 'id']);
        //return $this->hasMany(Category::className(), ['parent_id' => 'id'])->from(['subCategory' => Category::tableName()])->select(['id','name','parent_id']);

    }

    public function getImageUrl()
    {
      
        if($this->image){
         //   return Url::base(true).'/uploads/category/'. $this->image;
        // return Yii::$app->urlManagerFrontend->baseUrl.'/uploads/banner/original/'.$image;   
        // return   Yii::getAlias('@siteUrl').Yii::$app->urlManagerFrontend->baseUrl.'/uploads/category/'. $this->image;
         return Yii::$app->urlManagerFrontend->baseUrl.'/uploads//category//'.$this->image;
        }
   
        
    }

    public function getAdCount()
    {
        
        return $this->hasMany(Ad::className(), ['category_id' => 'id'])->andOnCondition(['ad.status' => Ad::STATUS_ACTIVE])->count();
   
        
    }



    

}
