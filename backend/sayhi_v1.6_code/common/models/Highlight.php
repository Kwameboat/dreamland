<?php
namespace common\models;

use Yii;
use yii\helpers\ArrayHelper;

use common\models\ReportedStory;
use common\models\ReportedHighlight;

class Highlight extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;
    const STATUS_BLOCKED=9;
    const TYPE_TEXT =1;
    const TYPE_IMAGE =2;
    const TYPE_VIDEO =3;

    const STORY_TYPE_ACTIVE =1;
    const STORY_TYPE_COMPLETE =2;
    
    public $imageFile;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'highlight';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            
            [['status', 'id','user_id','created_at'], 'integer'],
            [['image','name'], 'string'],
           
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
            'name' => Yii::t('app', 'Name'),
            'status' => Yii::t('app', 'Status'),
            'image' => Yii::t('app', 'Image'),
            'created_at' => Yii::t('app', 'Created'),
           
        ];
    }
   
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            // $this->created_by =   Yii::$app->user->identity->id;
          
        }else{
            // $this->updated_at = time();
            // $this->updated_by =   Yii::$app->user->identity->id;

        }

        
        return parent::beforeSave($insert);
    }
    

    public function getStatus()
    {
       if($this->status==$this::STATUS_INACTIVE){
           return 'Inactive';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }
    }
    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_INACTIVE => 'Inactive');
    }

    public function getImageUrl(){
        
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_HIGHTLIGHT,$this->image);
        
    }


    public function getUser()
    {
        return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }



    public function getReportedHighlightActive()
    {
        return $this->hasMany(ReportedHighlight::className(), ['highlight_id'=>'id'])->andOnCondition(['reported_highlight.status' => ReportedHighlight::STATUS_PENDING]);
        
    }

    public function getReportedHighlight()
    {
        return $this->hasMany(ReportedHighlight::className(), ['highlight_id'=>'id']);
        
    }

}
