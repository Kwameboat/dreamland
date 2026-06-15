<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\RelationInvitationRequest;

class RelationShip extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=1;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'relation_ship';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            
            [['id','male_relation_ship_id','female_relation_ship_id','use_once'], 'integer'],
            [['name'], 'string', 'max' => 256],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app','ID'),
            'name' => Yii::t('app','Name'),
            'male_relation_ship_id' => Yii::t('app','MaleRelationShipId'),
            'use_once' => Yii::t('app','UseOnce'),
            'female_relation_ship_id' => Yii::t('app','FemaleRelationShipId'),
        ];
    }

    public function getList(){
        return  $this->find()->select(['id','name'])->orderBy(['name'=>SORT_ASC ])->all(); 
    }

}
