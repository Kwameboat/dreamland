<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\Post;

class AgeGroup extends \yii\db\ActiveRecord
{

    const IMPRESSION_COUNT =1;
    const SOURCE_TYPE_POST =1;
    const SOURCE_TYPE_REELS =2;
    const SOURCE_TYPE_STORY =3;
    const STATUS_ACTIVE =1;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'age_group';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'string'],
            [['id','age_from','age_to','status'], 'integer'],
            // [['id','age_from','age_to','name'], 'required', 'on'=>'create']
            

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => Yii::t('app', 'Age Group'),
            'age_from' => Yii::t('app', 'Age From'),
            'age_to'=> Yii::t('app', 'Age To'),
            
        ];
    }


    public function getAge_group_name($age){       
        $command = Yii::$app->db->createCommand("
        SELECT * FROM age_group WHERE ".$age." BETWEEN age_from AND age_to AND status=".AgeGroup::STATUS_ACTIVE."");
        $result = $command->queryOne();
        if(!empty($result)){
            return  @$result['name'];
        }else{
            return[];
        }
        
    
    }

}
