<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\LiveTvCategory;

class TvBanner extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

   
    public $imageFile;
    public $transaction_id;
   

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tv_banner';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {

        return [
            [['name', 'banner_type','status','start_time','end_time'], 'required'],
            
            
            [['name','reference_id'], 'required','on'=>['create','update']],
            
            [['reference_id','cover_image','priority'], 'safe'],

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
        $fields[] = 'coverImageUrl';
        //$fields[] = 'categoryName';

        // $fields['categoryName'] = (function($model){
        //     return @$model->category->name;
        //    // return (@$model->isReported) ? 1: 0;
        // });
        
        return $fields;
    }


    public function extraFields()
    {
        // return [''];
    }
   
 

    public function getCategory(){

        return $this->hasOne(LiveTvCategory::className(), ['id' => 'category_id']);

    }



    public function getCoverImageUrl()
    {
        if($this->cover_image){
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_TV_BANNER,$this->cover_image);
           
        }else{
            return '';
        }
        
    }
    

}
