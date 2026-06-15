<?php
namespace api\modules\v1\models;

// use JetBrains\PhpStorm\Language;
use Yii;

class Language extends \yii\db\ActiveRecord
{
    const STATUS_DELETED = 0;
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;
    // public $counter;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'language';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','name'], 'required'],
            [['id'], 'integer'],
            
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app','ID'),
            'name' => Yii::t('app','Language Name')
            
        ];
    }

    public function fields()
    {
        $fields = parent::fields();

      
     //  $fields[] = 'counter';

    //    $fields['counter'] = (function($model){
            
    //       return (int)$model->counter;
    //    });
       


        return $fields;
    }


    







    

}
