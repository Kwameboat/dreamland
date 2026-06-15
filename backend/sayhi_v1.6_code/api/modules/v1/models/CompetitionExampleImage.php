<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;



class CompetitionExampleImage extends \yii\db\ActiveRecord
{
    const STATUS_DELETED=0;
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'competition_example_image';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'id'], 'integer'],
            [['image',], 'string', 'max' => 100]

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
        //$fields[] = 'audio_url';
       // $fields[] = 'imageUrl';
       //$fields[cate] = 'getuserLocation';
        return $fields;
    }
   
    public function getImageUrl(){
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_COMPETITION,$this->image);
        
        //return Yii::$app->params['pathUploadCompetition'] ."/".$this->image;
    }
    

}
