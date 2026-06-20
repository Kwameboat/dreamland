<?php
namespace common\components;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use common\models\Setting;
use Aws\Rekognition\RekognitionClient;
use Aws\Credentials\Credentials;

class ContentModeration extends Component
{


  public function validteContent($fileUrl)
  {
    $type = '';

    $modelSetting = new Setting();
    $settingResult = $modelSetting->getSettingData();
    if (!$settingResult) {
      return [false, ''];
    }

    $contentModerationGateway = (int) ($settingResult->content_moderation_gateway ?? 0);
    if ($contentModerationGateway < 1) {
      return [false, ''];
    }

    try {
      $headers = @get_headers($fileUrl, 1);
      if (!$headers) {
        return [false, ''];
      }
      $mimeType = is_array($headers['Content-Type'] ?? null)
        ? ($headers['Content-Type'][0] ?? '')
        : ($headers['Content-Type'] ?? '');
      if (strpos($mimeType, 'image') !== false) {
        $type = 'IMAGE';
      } elseif (strpos($mimeType, 'video') !== false) {
        $type = 'VIDEO';
      } else {
        return [false, ''];
      }
    } catch (\Throwable $e) {
      return [false, ''];
    }

    $dataFile = [];
    $dataFile['fileUrl'] = $fileUrl;
    $dataFile['type'] = $type;

    if ($contentModerationGateway == 1) {
      $dataFile['sightengineApiUser'] = $settingResult->sightengine_api_user;
      $dataFile['sightengineApiSecret'] = $settingResult->sightengine_api_secret;

      $responseData = $this->getSightengineContentData($dataFile);
      $content = $responseData['output'];
      //$type = $responseData['type'];

      if ($content) {
        if ($content['status'] == 'success') {
          $moderationReferenceId = $content['media']['id'];
          if ($type == 'IMAGE') {
            $frameResult = $this->checkFrameContent($content);
            $isProhabited = false;
            if ($frameResult['isNudity']) {
              $isProhabited = true;
            }

            return [$isProhabited, $moderationReferenceId];
          } else if ($type == 'VIDEO') {
            //echo 's';
            foreach ($content['data']['frames'] as $frame) {

              if ($frame['nudity']) {
                $frameResult = $this->checkFrameContent($frame);
                $isProhabited = false;
                if ($frameResult['isNudity']) {
                  $isProhabited = true;
                }
              }
              if ($isProhabited) {
                return [$isProhabited, $moderationReferenceId];
              }


            }
            return [$isProhabited, $moderationReferenceId];

          }
        }
      }
    } else if ($contentModerationGateway == 2) {


     
      $s3Key = $settingResult->aws_access_key_id;
      $s3Secret = $settingResult->aws_secret_key;
      $region = $settingResult->aws_region;
      $bucket = $settingResult->aws_bucket;

      $dataFile['s3Key'] = $s3Key;
      $dataFile['s3Secret'] = $s3Secret;
      $dataFile['region'] = $region;
      $dataFile['bucket'] = $bucket;


      $responseData = $this->validateWithAwsRekognition($dataFile);
      //$content = $responseData['output'];
      if ($responseData) {
        $isProhabited = $responseData['explicitContentDetected'];
        $moderationReferenceId = $responseData['jobId'];
        return [$isProhabited, $moderationReferenceId];

      }
    }

    return [false, ''];
  }

 


  public function getSightengineContentData($dataFile)
  {

   

    $fileUrl = $dataFile['fileUrl'];
    $type = $dataFile['type'];
    $sightengineApiUser = $dataFile['sightengineApiUser'];
    $sightengineApiSecret = $dataFile['sightengineApiSecret'];


    $params = array(
      'media' => new \CURLFile($fileUrl),
      // specify the models you want to apply
      'models' => 'nudity-2.0',
      'api_user' => $sightengineApiUser,
      'api_secret' => $sightengineApiSecret
    );

    $output = [];
    // Check if the file is an image
    $ch = '';
    if ($type == 'IMAGE') { //image
      $ch = curl_init('https://api.sightengine.com/1.0/check.json');
    }
    // Check if the file is a video
    elseif ($type == 'VIDEO') { //vidoe
      $ch = curl_init('https://api.sightengine.com/1.0/video/check-sync.json');
    }



    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    // print_r($response);
    curl_close($ch);
    $output = json_decode($response, true);
    $response = [
      'output' => $output,
      'type' => $type
    ];
    return $response;


  }
  public function checkFrameContent($frame)
  {
    //print_r($frame);
    $isNudity = false;
    if ($frame['nudity']) {
      if ($frame['nudity']['none'] <= 0.05) {

        $isNudity = true;
      }
    }

    $response['isNudity'] = $isNudity;
    return $response;

  }


  public function validateWithAwsRekognition($dataFile)
  {


    $fileUrl      = $dataFile['fileUrl'];
    $type         = $dataFile['type'];
    $s3Key        = $dataFile['s3Key'];
    $s3Secret     = $dataFile['s3Secret'];
    $region       = $dataFile['region'];
    $bucket       = $dataFile['bucket'];
    
    $urlParts = parse_url($fileUrl);
    $keyObjectName = '';
    if ($urlParts !== false && isset($urlParts['path'])) {
      $urlPath = $urlParts['path'];
      $keyObjectName = substr($urlPath, 1);

    }
  

    $credentials = new Credentials($s3Key, $s3Secret);
    // Instantiate the Rekognition client
    $rekognition = new RekognitionClient([
      'version' => 'latest',
      'region' => $region, // Change this to your preferred AWS region
      'credentials' => $credentials
    ]);
    $explicitContentDetected = false;
    $jobId = '';
    if ($type == 'IMAGE') { //image


      $result = $rekognition->detectModerationLabels([
        'Image' => [
          'S3Object' => [
            'Bucket' => $bucket,
            'Name' => $keyObjectName,
          ],
        ],
      ]);
      foreach ($result['ModerationLabels'] as $label) {

        if ($this->checkREkognitionLable($label)) {
          $explicitContentDetected = true;
          break;
        }
      }
    } elseif ($type == 'VIDEO') { //vidoe

      $result = $rekognition->startContentModeration([
        'Video' => [
          'S3Object' => [
            'Bucket' => $bucket,
            'Name' => $keyObjectName,
          ],
        ],

      ]);
      $jobId = $result['JobId'];

      $i = 0;

      $isFinished = false;
      $resultModerationResult = [];
      // $explicitContentDetected=false;
      while (!$isFinished) {
        //echo 'A';
        if ($i == 0) {
          sleep(10);
        } else {
          sleep(5);
        }

        $resultModerationResult = $rekognition->getContentModeration([
          'JobId' => $jobId,
        ]);
        if ($resultModerationResult['JobStatus'] == 'SUCCEEDED') {
          //echo 'SUC#' . $i;
          $isFinished = true;
        }

        $i++;
        if (($i > 10)) {
          $isFinished = true;
        }



      }

      if ($resultModerationResult['ModerationLabels']) {
        foreach ($resultModerationResult['ModerationLabels'] as $moderationLabel) {
          $label = $moderationLabel['ModerationLabel'];
          if ($label['Name'] === 'Explicit' && $label['Confidence'] >= 70) {
            $explicitContentDetected = true;
            break;
          }
        }
      }



    }
    $response = ['explicitContentDetected' => $explicitContentDetected, 'jobId' => $jobId];
    return $response;

  }

  public function checkREkognitionLable($label)
  {
    if ($label['Name'] === 'Explicit' && $label['Confidence'] >= 70) {
      return true;
    } else {
      return false;
    }

  }

}
?>