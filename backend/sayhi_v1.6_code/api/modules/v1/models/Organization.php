<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\CampaignImages;
use api\modules\v1\models\CampaignSearch;


class Organization extends \yii\db\ActiveRecord
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
        return 'organization';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'id','status','created_at','created_by', ], 'integer'],
            [['title', 'description'], 'string'],
            [['name', 'image','description', 'phone','email','address'], 'string', 'max' => 100],

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



    public function fields()
    {
        
        $fields = parent::fields();
        unset($fields['parent_id'],$fields['status'],$fields['priority'],$fields['leval']);
            $fields['campaginImage'] = (function($model){
                return @$model->ImageUrl;
            });
    
        return $fields;
    }

  
    public function getImageUrl()
    {
        if($this->image){
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_ORGNIZATION,$this->image);
           
        }else{
            return '';
        }
        
    }


    

    



    

}
