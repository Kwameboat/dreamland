<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\AudienceKeyword;

class PromotionInterest extends \yii\db\ActiveRecord
{
    const STATUS_DELETED = 0;
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;

    public $keywords;

    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'promotion_interest';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [

            [['id', 'audience_id', 'interest_id', 'status', 'created_at'], 'integer'],
            [['interest'], 'string', 'max' => '256'],
            // [['name'], 'required', 'on' => 'create'],
            // [['name'], 'required', 'on' => 'update'],
            // [['keywords','interest'],'safe']
           
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'audience_id' => Yii::t('app', 'Audience'),
            'interest_id' => Yii::t('app', 'interest_id'),
            'interest' => Yii::t('app', 'Interest'),
            'status' => Yii::t('app', 'Status'),            
            'created_at'=> Yii::t('app', 'Created At'),
            
        ];
    }
   
    // public function beforeSave($insert)
    // {
    //     if ($insert) {
    //         $this->created_at = time();
    //         $this->created_by =   Yii::$app->user->identity->id;
    //         $this->user_id =   Yii::$app->user->identity->id;
          
    //     }else{
    //         $this->updated_at = time();
    //         $this->updated_by =   Yii::$app->user->identity->id;
          
            
    //     }

        
    //     return parent::beforeSave($insert);
    // }
    
    public function updatePromotionInterest($audienceId,$recordsKeyword){
        $records=[];
        if($recordsKeyword){
            $records = explode(',',$recordsKeyword);
        }
       
        $values=[];
        foreach($records as $keyword){
            $interestId = trim($keyword);
            $interestName = $this->getInterestName($interestId);
          
            $dataInner['audience_id']          =  $audienceId;
            $dataInner['interest_id']        =  $interestId;
            $dataInner['interest']        =  $interestName;
            $dataInner['created_at']        =  time();
            $values[]=$dataInner;
        }
        
        $model =  new PromotionInterest();
        $model->deleteAll(['audience_id'=>$audienceId]);
        if(count($values)>0){

            Yii::$app->db
            ->createCommand()
            ->batchInsert('promotion_interest', ['audience_id','interest_id','interest','created_at'],$values)
            ->execute();
        }
    }
    
    public function getInterestName($interestId){
        $interest = Interest::find()->select('name')->where(['id'=>$interestId])->one();
       return $interest['name'];
    }
}
