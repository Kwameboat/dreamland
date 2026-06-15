<?php
namespace common\components;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
define( 'API_ACCESS_KEY', Yii::$app->params['apiKey.firebaseCloudMessaging']);

class PushNotification extends Component{
  
  
  
  public function sendPushNotification($deviceTokens, $pushData)
	{
		
		#API access key from Google API's Console
		
		$notificationContent['title']				=	trim($pushData['title']);
		$notificationContent['body']				=	trim($pushData['body']);

		$notificationContent['sound'] 				=   "default";
		$notificationContent['content_available'] 	=  true;
		$notificationContent['priority'] 			=   "high";
		
		$dataContent	=	$notificationContent;
		//$dataContent=[];
		if(isset($pushData['data'])){
			$dataContent = array_merge($dataContent,$pushData['data']);
			 
		}
		$data= [];
		$data['registration_ids'] 			= $deviceTokens;
		$data['notification']  				= $notificationContent;
		
		$data['data']  			= $dataContent;
		//echo '<pre>'; print_r($data);
		
		/*
		if(!$msg['silent']) { 
			$fields['notification'] 	= 	$msg;
		}
		*/
		//exit;
		
		$headers			=	array(
									'Authorization: key=' . API_ACCESS_KEY,
									'Content-Type: application/json'
								);

		#Send Reponse To FireBase Server	
		$ch = curl_init();
		curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
		curl_setopt( $ch,CURLOPT_POST, true );
		curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $data ) );
		$result	=	curl_exec($ch );
		curl_close( $ch );

		#Echo Result Of FireBase Server
		//echo $result;
		
		$jsonResult	=	json_decode($result ,true);
	//	echo '<pre>'; print_r($jsonResult); exit;
		return (int)@$jsonResult['success'];
		
	}

 
}
?>