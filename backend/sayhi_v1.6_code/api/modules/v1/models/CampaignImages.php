<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\User;
use api\modules\v1\models\Campaign;
use api\modules\v1\models\CampaignSearch;
// use api\modules\v1\models\Campaign;


class CampaignImages extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'campaign_images';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        
        return [
            [['id','campaign_id','status','updated_at','media_type','created_at'], 'integer'],
            [['image'], 'string'],
            
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
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_CAMPAGIN,$this->image);
           
        }else{
            return '';
        }
        
    }

}
