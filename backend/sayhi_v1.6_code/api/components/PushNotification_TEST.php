<?php
namespace api\components;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use Google\Client;

//define( 'API_ACCESS_KEY', Yii::$app->params['apiKey.firebaseCloudMessaging']);

class PushNotification extends Component
{

	public function sendPushNotification($deviceTokens, $pushData)
	{

		#API access key from Google API's Console
		try {


			$accessToken = $this->getAccessToken();

			$notificationContent['title'] = trim($pushData['title']);
			$notificationContent['body'] = trim($pushData['body']);

			$notificationContent['sound'] = "default";
			$notificationContent['content_available'] = true; // temp off
			$notificationContent['priority'] = "high";

			$dataContent = $notificationContent;
			//$dataContent=[];
			if (isset($pushData['data'])) {
				$dataContent = array_merge($dataContent, $pushData['data']);

			}
			/* original previus
				  $data = [];
				  $data['registration_ids'] = $deviceTokens;
				  $data['token'] = $deviceTokens;
				  $data['notification'] = $notificationContent;

				  
				  
				  $data['data'] = $dataContent;*/


			//print_r($dataContent);



			//$data1 = [];
			//$data1['message'] = $data;
			//echo '<pre>'; print_r($data);

			/*
						if(!$msg['silent']) { 
							$fields['notification'] 	= 	$msg;
						}
						*/

			// print_r(json_encode($data1));

			//exit;
			$dataContent = array_map('strval', $dataContent);

			$message = [
				'message' => [
					'token' => $deviceTokens[0],
					'notification' => [
						'title' => $notificationContent['title'],
						'body' => $notificationContent['body']
					],
					'data' => $dataContent
				],
			];
			//	print_r($message);

			$headers = array(
				'Authorization: Bearer ' . $accessToken,
				'Content-Type: application/json'
			);

			#Send Reponse To FireBase Server	
			$ch = curl_init();
		
			$serviceAccountPath = dirname(dirname(__DIR__)) . '/chat/serviceAccountKey.json';
			$fileContent = file_get_contents($serviceAccountPath);
			$json_data = json_decode($fileContent, true);
			$projectId = $json_data['project_id'];
			curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/" . $projectId . "/messages:send");

			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
			$result = curl_exec($ch);
			curl_close($ch);

			#Echo Result Of FireBase Server
			//echo $result;

			$jsonResult = json_decode($result, true);
			//echo '<pre>';

			//exit;
			//return (int) $jsonResult['success'];
			if (isset($jsonResult['name'])) {
				return $jsonResult['name'];
			} else {
				return false;
			}
		} catch (\Exception $ex) {
			return false;
			//echo 'err';
		}

	}

	public function sendPushNotification_OLD($deviceTokens, $pushData)
	{

		#API access key from Google API's Console

		$notificationContent['title'] = trim($pushData['title']);
		$notificationContent['body'] = trim($pushData['body']);

		$notificationContent['sound'] = "default";
		$notificationContent['content_available'] = true;
		$notificationContent['priority'] = "high";

		$dataContent = $notificationContent;
		//$dataContent=[];
		if (isset($pushData['data'])) {
			$dataContent = array_merge($dataContent, $pushData['data']);

		}
		$data = [];
		$data['registration_ids'] = $deviceTokens;
		$data['notification'] = $notificationContent;

		$data['data'] = $dataContent;
		//echo '<pre>'; print_r($data);

		/*
					if(!$msg['silent']) { 
						$fields['notification'] 	= 	$msg;
					}
					*/
		//exit;

		$headers = array(
			'Authorization: key=' . API_ACCESS_KEY,
			'Content-Type: application/json'
		);

		#Send Reponse To FireBase Server	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		$result = curl_exec($ch);
		curl_close($ch);

		#Echo Result Of FireBase Server
		//echo $result;

		$jsonResult = json_decode($result, true);
		//echo '<pre>'; print_r($jsonResult); exit;
		return (int) $jsonResult['success'];

	}


	public function getAccessToken()
	{


		// Path to the service account JSON file
		$serviceAccountPath = dirname(dirname(__DIR__)) . '/chat/serviceAccountKey.json';
		//$serviceAccountPath = Yii::getAlias('@frontend') . "/" . 'web/uploads/cert/serviceAccountKey.json';
		// Create a new client
		$client = new Client();
		$client->setAuthConfig($serviceAccountPath);
		$client->addScope('https://www.googleapis.com/auth/firebase.messaging');

		// Get the access token
		$accessToken = $client->fetchAccessTokenWithAssertion();

		if (isset($accessToken['access_token'])) {
			return $accessToken['access_token'];
			//echo 'Access Token: ' . $accessToken['access_token'];
		} else {
			return false;
			//echo 'Error fetching the access token!';
		}
	}




}
?>