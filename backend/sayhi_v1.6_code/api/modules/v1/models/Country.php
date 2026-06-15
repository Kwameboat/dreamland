<?php
namespace api\modules\v1\models;
use \yii\db\ActiveRecord;
/**
 * Country Model
 *
 */
class Country extends ActiveRecord
{
    const STATUS_ACTIVE=1;
    /**
	 * @inheritdoc
	 */
    public $fullname;
	public static function tableName()
	{
		return 'country';
	}

    /**
     * @inheritdoc
     */
    public static function primaryKey()
    {
        return ['id'];
    }

    /**
     * Define rules for validation
     */
    public function rules()
    {
        return [
            [[ 'name'], 'required']
        ];
    }

    public function getCity()
    {
       return  $this->hasMany(City::className(), ['country_id'=>'id']);
    //    ->andOnCondition(['post.status'=>Post::STATUS_ACTIVE , 'post.type'=>Post::TYPE_CLUB]);         
    }
}
