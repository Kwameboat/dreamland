<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\FeatureList;

class Setting extends \yii\db\ActiveRecord
{
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'setting';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [

  
            [['id','each_view_coin','min_coin_redeem','is_photo_post','is_video_post','is_stories','is_story_highlights','is_chat','is_audio_calling','is_video_calling','is_live','is_clubs','is_competitions','is_events','is_staranger_chat','is_profile_verification','is_light_mode_switching','is_watch_tv','is_podcasts','is_gift_sending','is_polls','is_dating','is_fund_raising','is_family_link_setup','is_post_promotion','is_location_sharing','is_gif_share','is_contact_sharing','each_view_price_promotion','is_coupon','is_reel','is_job','is_shop','is_live_user','is_offer','ads_auto_approve','content_moderation_gateway','sms_gateway','storage_system','subscribe_active_condition_follower','subscribe_active_condition_post'],'integer'],
            [['min_widhdraw_price','per_coin_value','available_coin','commission_on_gift'], 'number'],
            [['email','phone','site_name','facebook','youtube','twitter','linkedin','pinterest','instagram','in_app_purchase_id','release_version','site_url','user_p_id','maximum_video_duration_allowed','free_live_tv_duration_to_view','latest_app_download_link','disclaimer_url','privacy_policy_url','terms_of_service_url','giphy_api_key','agora_api_key','google_map_api_key','interstitial_ad_unit_id_for_android','interstitial_ad_unit_id_for_IOS','reward_interstitl_ad_unit_id_for_android','reward_interstitial_ad_unit_id_for_IOS','banner_ad_unit_id_for_android','banner_ad_unit_id_for_IOS','fb_interstitial_ad_unit_id_for_android','fb_interstitial_ad_unit_id_for_IOS','fb_reward_interstitial_ad_unit_id_for_android','fb_reward_interstitial_ad_unit_id_for_IOS','network_to_use','stripe_publishable_key','stripe_secret_key','paypal_merchant_id','paypal_public_key','paypal_private_key','razorpay_api_key','website_url','music_url'], 'string', 'max' => 256],


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
    public function extraFields()
    {
       
        return ['featureList'];
    }


    public function getEnableDisableDropDownData()
    {
        return array(1 => 'Enable', 0 => 'Disable');
    }
    
    public function getSettingData()
    {
        return $this->find()->orderBy(['id'=>SORT_DESC])->one();
    }
    public function getFeatureList()
    {
        
        $modelFeatureList =  new FeatureList();
        return $modelFeatureList->getListData(1);
       
    }
   
   

    
    

}
