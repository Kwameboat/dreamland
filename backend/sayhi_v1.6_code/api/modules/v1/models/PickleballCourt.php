<?php
namespace api\modules\v1\models;
use Yii;

use api\modules\v1\models\PickleballMatch;


class PickleballCourt extends \yii\db\ActiveRecord
{
    
    const STATUS_ACTIVE=10;
    const STATUS_DELETED = 0;
    const STATUS_BLOCKED=9;

    const TYPE_OUTDOOR=1;
    const TYPE_INDOOR=2;

    public $distance;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pickleball_court';
    }

    

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[ 'name','address','latitude','longitude','image'], 'string'],
            [['id','type','status', 'created_at','created_by','rating'], 'integer'],
            [['distance'], 'number'],
            ['type', 'in', 'range' => [1,2]],
            [['type','name','address' ], 'required','on'=>'create'],
            [['distance'], 'safe']
            
            
            
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

    
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->created_by  =   Yii::$app->user->identity->id;
           
        }
        return parent::beforeSave($insert);
    }

    public function fields()
    {
        $fields = parent::fields();
        $fields[] = 'imageUrl';
        $fields['distance'] = (function($model){
            return @($model->distance)?$model->distance:0;
       });
        return $fields;
    }
    
    public function extraFields()
    {
        return [];
    }
    public function getImageUrl()
    {
        if($this->image){
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_PICKLEBALL_COURT ,$this->image);
           
        }else{
            return '';
        }
        
    }

    public function getPickleballMatch()
    {
        return $this->hasMany(PickleballMatch::className(), ['court_id' => 'id'])->andOnCondition(['pickleball_match.status' =>PickleballMatch::STATUS_COMPLETED]);

    }
    
   
    
}
