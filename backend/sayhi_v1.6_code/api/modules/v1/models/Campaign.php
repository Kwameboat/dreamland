<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\CampaignImages;
use api\modules\v1\models\Organization;
use api\modules\v1\models\CampaignSearch;
use api\modules\v1\models\CampaignFavorite;

use api\modules\v1\models\CampaignComment;

class Campaign extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;
    const TYPE_RUNNING_CAMPAIGN=1;
    const TYPE_EXPIRED_CAMPAIGN=2;
   
    public $imageFile;
    public $transaction_id;
    public $payments;
    public $amount;
    public $is_active;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'campaign';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['campaigner_id', 'id','campaign_for_id','status','created_at','created_by','updated_at','updated_by','start_date', 'end_date','category_id' ], 'integer'],
            [['title', 'description'], 'string'],
            [['target_value', 'raised_value' ], 'number'],
            [['start_date','end_date'], 'safe'],
            [['title', 'cover_image'], 'string', 'max' => 100],
            [['id','amount'], 'required','on'=>'campaignPayment'],
            [['payments','is_active'], 'safe'],
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
    

    // Add  Extere  Feilds

public function fields()
{
    
    $fields = parent::fields();
    unset($fields['parent_id'],$fields['status'],$fields['priority'],$fields['leval']);
    $fields[] = 'coverImageUrl';
        $fields['campaginAllImage'] = (function($model){
            return @$model->competitionPosition;
        });
   
        $fields['compaigner'] = (function($model){
            return @$model->organization;
        });


        $fields['compaigner_for'] = (function($model){
            return @$model->organizationFor;
        });

        $fields['is_favorite'] = (function($model){
            return (@$model->isFavorite) ? 1: 0;
        });
        $fields['total_donors'] = (function($model){
            return (int)@$model->totalDonors;
        });
        $fields['is_donor'] = (function($model){
            return ($model->isDonor)? 1:0;
        });

    return $fields;
}

    public function extraFields()
    {
    
        return ['donorsDetails','categoryDetails'];
    }

    public function getCoverImageUrl()
    {
        if($this->cover_image){
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_CAMPAGIN,$this->cover_image);
           
        }else{
            return '';
        }
        
    }

    public function getCampaginImageUrl()
    {
        if($this->image){
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_CAMPAGIN,$this->image);
           
        }else{
            return '';
        }
        
    }



    public function getCompetitionPosition()
    {
        return $this->hasMany(CampaignImages::className(), ['campaign_id' => 'id']);

    }

    public function getOrganization()
    {
        return $this->hasOne(Organization::className(), ['id' => 'campaigner_id']);

    }

    public function getOrganizationFor()
    {
        return $this->hasOne(Organization::className(), ['id' => 'campaign_for_id']);

    }
    

    public function updateCommentCounter($campaignerId){

        $result = $this->findOne($campaignerId);
        $model = new CampaignComment();
        $totalCount = $model->find()->where(['post_id'=>$campaignerId,'status'=>$model::STATUS_ACTIVE])->count();
        $result->total_comment = $totalCount;
        if($result->save(false)){
           return  $totalCount;
        }else{
            return false;
        }
              
    }
    public function getIsFavorite()
    {
        return $this->hasOne(CampaignFavorite::className(), ['campaign_id'=>'id'])->andOnCondition(['campaign_favorite.user_id' => Yii::$app->user->identity->id]);
        
    }

    public function getCampaignMyFavorite()
    {
        return $this->hasMany(CampaignFavorite::className(), ['campaign_id'=>'id']);
        
    }

    // public function getCampaignMyFavorite()
    // {
    //     return $this->hasMany(CampaignFavorite::className(), ['campaign_id'=>'id']);
        
    // }

     public function getTotalDonors(){
        return $this->hasMany(Payment::className(), ['campaign_id'=>'id'])->groupBy('payment.user_id')->count();
     }
     public function getIsDonor(){
        return $this->hasMany(Payment::className(), ['campaign_id'=>'id'])->andOnCondition(['payment.user_id' => @Yii::$app->user->identity->id])->count();
     }
     
   
    public function getDonorsDetails(){
        return $this->hasMany(User::className(), ['id' => 'user_id'])
        ->viaTable('payment', ['campaign_id' => 'id'])->orderBy(['id' => SORT_DESC])->limit(5)
        ->select(['user.id', 'user.username', 'user.email','user.bio','user.country_code','user.phone','user.country','user.sex','user.dob']);
    }

    public function getCategoryDetails(){
        return $this->hasOne(Category::className(), ['id'=>'category_id']);
     }

}
