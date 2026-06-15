<?php
namespace common\components;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use Twilio\Rest\Client;
use common\models\Setting;

class Sms extends Component{
	const SOURCE_TWILIO=1;
	const SOURCE_SMS91=2;
	const SOURCE_FIREBASE=3;
	const SOURCE_VONAGE=4;
  
  
  public function sendSms($phoneNumber, $data)
	{
		if (Yii::$app->params['siteMode'] != 1) {
			return true;

		}
		$modelSetting = new Setting();
        $resultSetting = $modelSetting->getSettingData();
		//print_r($resultSetting);
		$smsGateway =  $resultSetting->sms_gateway;

		$countryCode= $phoneNumber['countryCode'];
		$phoneNumber= $phoneNumber['phoneNumber'];
		
		$messageStr = $data['message'];
		$mediaUrl =   @$data['mediaUrl'];
		

		if($smsGateway ==Sms::SOURCE_TWILIO){
			$toNumber = '+' . $countryCode . $phoneNumber;

			$sid 			= $resultSetting->twilio_sid;
			$tokenTwilio 	= $resultSetting->twilio_token;
			$smsFromTwilio 	= $resultSetting->twilio_number;

			$twilio = new Client($sid, $tokenTwilio);

			//$otpString = "OTP:" . $otp;

			$messgeData = [];
			$messgeData['from'] = $smsFromTwilio;
			$messgeData['body'] = $messageStr;

			if($mediaUrl){
				$messgeData['mediaUrl'] = [$mediaUrl];
			}
			
			$message = $twilio->messages
				->create(
					$toNumber,
					$messgeData
				);
			if ($message->sid) {

				return true;

			} else {

				return false;

			}
		}else if($smsGateway ==Sms::SOURCE_SMS91){
			
			$authKey =  $resultSetting->msg91_authKey;
			//Multiple mobiles numbers separated by comma
			$mobileNumber = $countryCode . $phoneNumber;
			//Sender ID,While using route4 sender id should be 6 characters long.
			$senderId = $resultSetting->msg91_sender_id;
			//Your message to send, Add URL encoding here.
			$message = urlencode($messageStr);
			//Define route 
			$route = "default";
			//Prepare you post parameters
			$postData = array(
				'authkey' => $authKey,
				'mobiles' => $mobileNumber,
				'message' => $message,
				'sender' => $senderId,
				'route' => $route
			);

			//API URL
			$url="http://api.msg91.com/api/sendhttp.php";

			// init the resource
			$ch = curl_init();
			curl_setopt_array($ch, array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $postData
				//,CURLOPT_FOLLOWLOCATION => true
			));
			//Ignore SSL certificate verification
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			//get response
			$output = curl_exec($ch);
			//Print error if any
			if(curl_errno($ch))
			{
				return false;
				//echo 'error:' . curl_error($ch);
			}
			curl_close($ch);
			if($output){
				return true;
			}else{
				return false;
			}
			
			//echo $output;
		}else if($smsGateway ==Sms::SOURCE_VONAGE){
			
			$authKey 	=  $resultSetting->vonage_api_key;
			$authSecret =  $resultSetting->vonage_api_secret;

			
			//Multiple mobiles numbers separated by comma
			$mobileNumber = $countryCode . $phoneNumber;
			$basic  = new \Vonage\Client\Credentials\Basic($authKey, $authSecret);
			$client = new \Vonage\Client($basic);

			$brandName = Yii::$app->params['siteName'];
			$brandName = str_replace(' ', '', $brandName);
			
			$response = $client->sms()->send(
				new \Vonage\SMS\Message\SMS($mobileNumber, $brandName, $messageStr)
			);
			
			$message = $response->current();
			
			if ($message->getStatus() == 0) {
				return true;
			} else {
				return false;
			}
			
		}else if($smsGateway ==Sms::SOURCE_FIREBASE ){

			return true;
		}

	}

 
}
?>