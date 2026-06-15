<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\FeatureEnabled;

class FeatureList extends \yii\db\ActiveRecord
{

    
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'feature_list';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type','section','priority','status'], 'integer'],
            [['name','feature_key'], 'string'],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            
        ];
    }
    public function fields()
    {
        $fields = parent::fields();
     
        return $fields;
    }
    public function getListData_old($type,$userId=null){
        $modelFeatureList =  new FeatureList();
        $modelFeatureEnabled =  new FeatureEnabled();
        
        $featureListRecord = $modelFeatureList->find()->select(['id','name','feature_key','type'])->where(['status'=>$modelFeatureList::STATUS_ACTIVE])->asArray()->all();
        $featureEnabledRecord = $modelFeatureEnabled->find()->where(['type'=>$type])->asArray()->all();
        
        $query = $modelFeatureEnabled->find()->where(['type'=>$type]);
        if($type==2){
            $query->andWhere(['user_id'=>$userId]);
        }
        $featureEnabledRecord = $query->asArray()->all();

        $featureList = array();
         foreach ($featureListRecord as $key => $item) {
            $item['is_active'] =0;
            $found_key = array_search($item['id'], array_column($featureEnabledRecord, 'feature_id'));
            if(is_int($found_key)){
                $item['is_active'] =1;
            }
          //  $featureList[$item['section']][$key] = $item;
             $featureList[] = $item;

        }
        //ksort($featureList, SORT_NUMERIC);
        return $featureList;
       


    }

    public function getListData_main($type,$userId=null){
        $modelFeatureList =  new FeatureList();
        $modelFeatureEnabled =  new FeatureEnabled();
        
        $featureListRecord = $modelFeatureList->find()->select(['id','name','feature_key','type'])->where(['status'=>$modelFeatureList::STATUS_ACTIVE])->asArray()->all();
        $featureEnabledMainRecord = $modelFeatureEnabled->find()->where(['type'=>1])->asArray()->all();
        $featureEnabledRecord = $modelFeatureEnabled->find()->where(['type'=>$type])->asArray()->all();
        
        $query = $modelFeatureEnabled->find()->where(['type'=>$type]);
        if($type==2){
            $query->andWhere(['user_id'=>$userId]);
        }
        $featureEnabledRecord = $query->asArray()->all();

        $featureList = array();

        foreach ($featureListRecord as $key => $item) {
            $item['is_active'] =0;
            $found_key = array_search($item['id'], array_column($featureEnabledRecord, 'feature_id'));
            if(is_int($found_key)){
                $enabledRecords = $featureEnabledRecord[$found_key];
                if($enabledRecords){
                    if($enabledRecords['is_enabled']){
                        $item['is_active'] =1;        
                    }
                }
            }else{
                $item['is_active'] =1;  
            }
            $featureList[] = $item;
        }
        return $featureList;

    }
    public function getListData($type,$userId=null){
        $modelFeatureList =  new FeatureList();
        $modelFeatureEnabled =  new FeatureEnabled();
        
        $featureListRecord = $modelFeatureList->find()->select(['id','name','feature_key','type'])->where(['status'=>$modelFeatureList::STATUS_ACTIVE])->asArray()->all();
        $featureEnabledMainRecord = $modelFeatureEnabled->find()->where(['type'=>1])->asArray()->all();
        $featureEnabledRecord = $modelFeatureEnabled->find()->where(['type'=>$type])->asArray()->all();
        
        $query = $modelFeatureEnabled->find()->where(['type'=>$type]);
        if($type==2){
            $query->andWhere(['user_id'=>$userId]);
        }
        $featureEnabledRecord = $query->asArray()->all();

        $featureList = array();

        foreach ($featureListRecord as $key => $item) {
            $item['is_active'] =0;
            $found_key = array_search($item['id'], array_column($featureEnabledRecord, 'feature_id'));
            $found_main_key = array_search($item['id'], array_column($featureEnabledMainRecord, 'feature_id'));
            if(is_int($found_key)){
                $enabledRecords = $featureEnabledRecord[$found_key];
                if($enabledRecords){
                    if($enabledRecords['is_enabled']){
                        $item['is_active'] =1;        
                    }
                }
            }
            if (is_int($found_main_key)) {
                $enabledMainRecords = $featureEnabledMainRecord[$found_main_key];
                if($enabledMainRecords){
                    if($enabledMainRecords['is_enabled'] ){
                        if(!is_int($found_key)){
                            $item['is_active'] =1; 
                        }       
                    }else{
                        $item['is_active'] =0; 
                    }
                }
            }


            $featureList[] = $item;
        }
        return $featureList;

    }
    

}
