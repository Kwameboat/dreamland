<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;

class Faq extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

   
   
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'faq';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            

            [['question', 'answer'], 'required'],
            [['id'], 'integer'],
            [['question', 'answer'], 'string'],
            [['question', 'answer'], 'required','on'=>['create','update']],          
            
            
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

}
