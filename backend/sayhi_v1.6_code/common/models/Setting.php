<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\ForbiddenHttpException;
use yii\helpers\Url;
use yii\base\ErrorException;

/**
 * This is the model class for table "countryy".
 *
 */
class Setting extends \yii\db\ActiveRecord
{

    const SETTING_DB_ID=1;

    const CONTENT_MODERATION_GATEWAY_NO = 0;
    const CONTENT_MODERATION_GATEWAY_SIGHTENGINE = 1;
    const CONTENT_MODERATION_GATEWAY_AMAZON_REKOGNITION = 2;
    
    const SMS_GATEWAY_TWILIO=1;
	const SMS_GATEWAY_SMS91=2;
	const SMS_GATEWAY_FIREBASE=3;
    const SMS_GATEWAY_VONAGE=4;

    
    const STORAGE_SYSTEM_LOCAL_SERVER=1;
    const STORAGE_SYSTEM_S3=2;
    const STORAGE_SYSTEM_AZURE=3;
    const STORAGE_SYSTEM_WASABI=4;
    
    public $feature;

    

    
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
            
            [['id','each_view_coin','min_coin_redeem','is_photo_post','is_video_post','is_stories','is_story_highlights','is_chat','is_audio_calling','is_video_calling','is_live','is_clubs','is_competitions','is_events','is_staranger_chat','is_profile_verification','is_light_mode_switching','is_watch_tv','is_podcasts','is_gift_sending','is_polls','is_dating','is_fund_raising','is_family_link_setup','is_post_promotion','is_location_sharing','is_gift_share','is_contact_sharing','is_photo_share','is_video_share','is_files_share','is_gift_share','is_audio_share','is_audio_share','is_drawing_share','is_user_profile_share','is_club_share','is_reply','is_forward','is_star_message','is_events_share','is_chat_gpt','is_coupon','is_reel','is_job','is_shop','is_live_user','is_offer','ads_auto_approve','is_photo_video_edit','is_two_factor_auth','content_moderation_gateway','sms_gateway','storage_system','subscribe_active_condition_follower','subscribe_active_condition_post'],'integer'],
 
            [['min_widhdraw_price','per_coin_value','available_coin','commission_on_gift'], 'number'],
            [['email','phone','site_name','facebook','youtube','twitter','linkedin','pinterest','instagram','in_app_purchase_id','release_version','site_url','user_p_id','maximum_video_duration_allowed','free_live_tv_duration_to_view','latest_app_download_link','disclaimer_url','privacy_policy_url','terms_of_service_url','giphy_api_key','agora_api_key','agora_app_certificate','google_map_api_key','interstitial_ad_unit_id_for_android','interstitial_ad_unit_id_for_IOS','reward_interstitl_ad_unit_id_for_android','reward_interstitial_ad_unit_id_for_IOS','banner_ad_unit_id_for_android','banner_ad_unit_id_for_IOS','fb_interstitial_ad_unit_id_for_android','fb_interstitial_ad_unit_id_for_IOS','fb_reward_interstitial_ad_unit_id_for_android','fb_reward_interstitial_ad_unit_id_for_IOS','network_to_use','stripe_publishable_key','stripe_secret_key','paypal_merchant_id','paypal_public_key','paypal_private_key','razorpay_api_key','moments_name','theme_color','theme_font','theme_light_background_color','theme_light_text_color','theme_dark_background_color','theme_dark_text_color','website_name','website_url','chat_gpt_key','imgly_key','google_play_store_url','apple_app_store_url','music_url','sightengine_api_user','sightengine_api_secret','twilio_sid','twilio_token','twilio_number','msg91_authKey','msg91_sender_id','aws_access_key_id','aws_secret_key','aws_region','aws_bucket','aws_access_url','azure_account_name','azure_account_key','azure_container','wasabi_access_key','wasabi_secret_key','wasabi_region','wasabi_bucket','wasabi_access_url','vonage_api_key','vonage_api_secret'], 'string', 'max' => 256],
           

            [['aws_access_key_id','aws_secret_key','aws_region','aws_bucket','aws_access_url'], 'required', 'on' => 'storageSetting', 'when' => function($model) {
                return $model->storage_system == Setting::STORAGE_SYSTEM_S3; 
            }, 'whenClient' => "function(attribute, value) {
                return $('#storage_system_id').val() == '2'; // Client-side condition
            }"],

            [['wasabi_access_key','wasabi_secret_key','wasabi_region','wasabi_bucket','wasabi_access_url'], 'required', 'on' => 'storageSetting', 'when' => function($model) {
                return $model->storage_system == Setting::STORAGE_SYSTEM_S3; 
            }, 'whenClient' => "function(attribute, value) {
                return $('#storage_system_id').val() == '4'; // Client-side condition
            }"],

            [['azure_account_name','azure_account_key','azure_container'], 'required', 'on' => 'storageSetting', 'when' => function($model) {
                return $model->storage_system == Setting::STORAGE_SYSTEM_AZURE; 
            }, 'whenClient' => "function(attribute, value) {
                return $('#storage_system_id').val() == '3'; // Client-side condition
            }"],

            [['sightengine_api_user','sightengine_api_secret'], 'required', 'on' => 'contentModerationSetting', 'when' => function($model) {
                return $model->content_moderation_gateway == Setting::CONTENT_MODERATION_GATEWAY_SIGHTENGINE; 
            }, 'whenClient' => "function(attribute, value) {
                return $('#content_moderation_gateway_id').val() == '1'; 
            }"],

            //sms
            [['twilio_sid','twilio_token','twilio_number'], 'required', 'on' => 'smsSetting', 'when' => function($model) {
                return $model->sms_gateway == Setting::SMS_GATEWAY_TWILIO; 
            }, 'whenClient' => "function(attribute, value) {
                return $('#sms_gateway_id').val() == '1'; 
            }"],
            [['msg91_authKey','msg91_sender_id'], 'required', 'on' => 'smsSetting', 'when' => function($model) {
                return $model->sms_gateway == Setting::SMS_GATEWAY_SMS91; 
            }, 'whenClient' => "function(attribute, value) {
                return $('#sms_gateway_id').val() == '2'; 
            }"],
            [['vonage_api_key','vonage_api_secret'], 'required', 'on' => 'smsSetting', 'when' => function($model) {
                return $model->sms_gateway == Setting::SMS_GATEWAY_VONAGE; 
            }, 'whenClient' => "function(attribute, value) {
                return $('#sms_gateway_id').val() == '4'; 
            }"],

            
            [['feature'], 'safe'],
            


        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [

            'id' => 'ID',
            'site_name' => Yii::t('app', 'Site Name'),
            'each_view_coin' => Yii::t('app', 'Post View Coin'),
            'user_p_id'=> Yii::t('app', 'Envento purcahse code'),
            'razorpay_api_key'=> Yii::t('app', 'Razorpay api key'),
            'paypal_merchant_id'=> Yii::t('app', 'Braintree  Merchant Id'),
            'paypal_public_key'=> Yii::t('app', 'Braintree Public key'),
            'paypal_private_key'=> Yii::t('app', 'Braintree Private key'),
            'agora_api_key'=> Yii::t('app', 'Agora App ID'),
            
            'is_photo_post' => Yii::t('app', 'Photo Post'),
            'is_video_post' => Yii::t('app', 'Video Post'),
            'is_stories' => Yii::t('app', 'Stories'),
            'is_story_highlights' => Yii::t('app', 'Story Highlights'),
            'is_chat' => Yii::t('app', 'Chat'),
            'is_audio_calling' => Yii::t('app', 'Audio Calling'),
            'is_video_calling' => Yii::t('app', 'Video Calling'),
            'is_live' => Yii::t('app', 'Live'), 
            'is_clubs' => Yii::t('app', 'Clubs'),
            'is_competitions' => Yii::t('app', 'Competitions'),
            'is_events' => Yii::t('app', 'Events'),
            'is_staranger_chat' => Yii::t('app', 'Staranger Chat'),
            'is_profile_verification' => Yii::t('app', 'Profile Verification'),
            'is_light_mode_switching'=>Yii::t('app', 'Light Mode Switching'),
            'is_watch_tv' => Yii::t('app', 'Watch Tv'),
            'is_podcasts' => Yii::t('app', 'Podcasts'),
            'is_gift_sending' => Yii::t('app', 'Gift Sending'),

            
            'is_contact_sharing' => Yii::t('app', 'Contact Share'),
            'is_location_sharing' => Yii::t('app', 'Location Share'),
            'is_photo_share' => Yii::t('app', 'Photo Share'),
            'is_video_share' => Yii::t('app', 'Video Share'),
            'is_files_share' => Yii::t('app', 'Files Share'),
            'is_gift_share' => Yii::t('app', 'Gif Share'),
            'is_audio_share' => Yii::t('app', 'Audio Share'),
            'is_drawing_share' => Yii::t('app', 'Drawing Share'),
            'is_user_profile_share' => Yii::t('app', 'User Profile Share'),
            'is_club_share' => Yii::t('app', 'Club Share'),
            'is_events_share' => Yii::t('app', 'Events Share'),
            'is_reply' => Yii::t('app', 'Reply'),
            'is_forward' => Yii::t('app', 'Forward'),
            'is_star_message' => Yii::t('app', 'Star Message'),

            'is_polls' => Yii::t('app', 'Polls'),
            'is_dating' => Yii::t('app', 'Dating'),
            'is_fund_raising' => Yii::t('app', 'Fund Raising'),
            'is_family_link_setup' => Yii::t('app', 'Family links Setup'),
            'is_post_promotion' => Yii::t('app', 'Post Promotion'),
            'is_chat_gpt' => Yii::t('app', 'Chat GPT'),
            
            'moments_name' => Yii::t('app', 'Moments name'),
            'theme_color' => Yii::t('app', 'Theme color'),
            'theme_font' => Yii::t('app', 'Theme Font'),
            'theme_light_background_color' => Yii::t('app', 'Theme light background color'),
            'theme_light_text_color' => Yii::t('app', 'Theme light text color'),
            'theme_dark_background_color' => Yii::t('app', 'Theme dark background color'),
            'theme_dark_text_color' => Yii::t('app', 'Theme dark text color'),
            'chat_gpt_key' => Yii::t('app', 'Chat Gpt Key'),
            'is_coupon' => Yii::t('app', 'Coupon'),
            'is_reel' => Yii::t('app', 'Reel'),
            'is_job' => Yii::t('app', 'Job'),
            'is_shop' => Yii::t('app', 'Shop'),
            'is_live_user' => Yii::t('app', 'Live User'),
            'is_offer' => Yii::t('app', 'Offer'),
            'ads_auto_approve' => Yii::t('app', 'Ads Auto Approve'),
            'is_two_factor_auth' => Yii::t('app', 'Allow Two Factor Authentication on admin login'),
            'music_url' => Yii::t('app', 'Music URL'),
            'google_play_store_url' => Yii::t('app', 'Google play store URL'),
            'apple_app_store_url' => Yii::t('app', 'Apple App store URL'),
            'sightengine_is_content_moderation' => Yii::t('app', 'Is Enable?'),
            'sightengine_api_user' => Yii::t('app', 'Api User'),
            'sightengine_api_secret' => Yii::t('app', 'Api Secret'),
            'sms_gateway' => Yii::t('app', 'SMS Gateway'),
            'twilio_sid' => Yii::t('app', 'Twilio SID'),
            'twilio_token' => Yii::t('app', 'Twilio Token'),
            'twilio_number' => Yii::t('app', 'Twilio phone number'),
            'msg91_authKey' => Yii::t('app', 'MSG91 AuthKey'),
            'msg91_sender_id' => Yii::t('app', 'MSG91 Sender Id'),
            'vonage_api_key' => Yii::t('app', 'API Key'),
            'vonage_api_secret' => Yii::t('app', 'API Secret'),


            'storage_system' => Yii::t('app', 'Storage Destination'),
            'aws_access_key_id' => Yii::t('app', 'Access Key ID'),
            'aws_secret_key' => Yii::t('app', 'Secret Key'),
            'aws_region' => Yii::t('app', 'Region'),
            'aws_bucket' => Yii::t('app', 'Bucket'),
            'aws_access_url' => Yii::t('app', 'Access Url'),

            'wasabi_access_key' => Yii::t('app', 'Access Key'),
            'wasabi_secret_key' => Yii::t('app', 'Secret Key'),
            'wasabi_region' => Yii::t('app', 'Region'),
            'wasabi_bucket' => Yii::t('app', 'Bucket'),
            'wasabi_access_url' => Yii::t('app', 'Endpint Url'),

            'azure_account_name' => Yii::t('app', 'Account Name'),
            'azure_account_key' => Yii::t('app', 'Account Key'),
            'azure_container' => Yii::t('app', 'Container')
            
        ];
    }

    public static function getValueByKey($key)
    {
        $model= new Setting();
        $model->find()->one();
        return 'YES';
    }
    public function getEnableDisableDropDownData()
    {
        return array(1 => 'Enable', 0 => 'Disable');
    }

   
    public function updateSettingData()
    {
        $res= $this->find()->orderBy(['id'=>SORT_DESC])->one();
        $res->site_url = Yii::$app->params['siteUrl'];
        $res->save(false);
    }

public function getSettingData()
{
    $setting = $this->find()->orderBy(['id' => SORT_DESC])->one();
    if (!$setting) {
        $setting = new self();
        $setting->id = self::SETTING_DB_ID;
        $setting->user_p_id = 'bypassed';
        $setting->save(false);
    }
    return $setting;
}
    
    public function getGraphSetting()
{
    $resultSetting = $this->getSettingData();
    if (!$resultSetting->user_p_id) {
        $resultSetting->user_p_id = 'bypassed';
        $resultSetting->save(false);
    }
    return true;
}

    public function CallAPI($method, $url, $data = false)
    {
        $curl = curl_init();

        switch ($method)
        {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);

                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_PUT, 1);
                break;
            default:
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }

        // Optional Authentication:
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        //curl_setopt($curl, CURLOPT_USERPWD, "username:password");
        
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);
        
        curl_close($curl);

        return $result;
    }
    

    public function getNetworkUseDropDownData()
    {
        return array(1 => 'Facebook', 2 => 'Google');
    }

    public function getSmsGatewayDropDownData()
    {
        return array(1 => 'Twilio', 2 => 'SMS91', 3 => 'Firebase',4 => 'Vonage');
    }

    public function getStorageSystemDropDownData()
    {
        return array(Setting::STORAGE_SYSTEM_LOCAL_SERVER => 'Server Storage', Setting::STORAGE_SYSTEM_S3 => 'AWS S3', Setting::STORAGE_SYSTEM_AZURE => 'Azure Storage',Setting::STORAGE_SYSTEM_WASABI => 'Wasabi Storage');
    }
    public function getContentModerationDropDownData()
    {
        return array(Setting::CONTENT_MODERATION_GATEWAY_NO => 'No Content Moderation', Setting::CONTENT_MODERATION_GATEWAY_SIGHTENGINE => 'Sightengine', Setting::CONTENT_MODERATION_GATEWAY_AMAZON_REKOGNITION => 'Amazon Rekognition');
    }



    public function getFontDropDownData()
    {
        return array('Arial' => 'Arial', 'Verdana' => 'Verdana' , 'Tahoma' => 'Tahoma' , 'Georgia'=>'Georgia');
    }

    public function getAutoAdsDropDownData()
    {
        return array(1 => 'Yes', 0 => 'No');
    }
}
