<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;


use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use League\Flysystem\Filesystem;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use api\modules\v1\models\Setting;
use Aws\S3\S3Client;


class FileUpload extends \yii\db\ActiveRecord
{
    const TYPE_AD_IMAGES = 1;
    const TYPE_COLLECTION = 2;
    const TYPE_STORY = 3;
    const TYPE_HIGHTLIGHT = 4;
    const TYPE_CHAT = 5;
    const TYPE_USER = 6;
    const TYPE_POST = 7;
    const TYPE_COMPETITION = 8;
    const TYPE_CATEGORY = 9;
    const TYPE_LIVE_TV = 10;
    const TYPE_GIFT = 11;
    const TYPE_VERIFICATION = 12;
    const TYPE_EVENT = 13;
    const TYPE_COUPON = 14;
    const TYPE_EVENT_ORGANISOR = 15;
    const TYPE_TV_SHOW = 16;
    const TYPE_TV_SHOW_EPISODE = 17;
    const TYPE_REEL_AUDIO = 18;
    const TYPE_TV_BANNER = 19;
    const TYPE_PODCAST = 20;
    const TYPE_PODCAST_SHOW = 22;
    const TYPE_PODCAST_BANNER =23;

    const TYPE_ORGNIZATION =21;
    const TYPE_CAMPAGIN =24;
    const TYPE_BUSINESS =25;
    const TYPE_INTEREST =26;
    const TYPE_JOB_APPLICATION =28;
    const TYPE_PROMOTIONAL_AD =29;
    const TYPE_PROMOTIONAL_BANNER = 30;
    const TYPE_PICKLEBALL_COURT = 31;
    
    const STORAGE_SYSTEM_LOCAL = 1;
    const STORAGE_SYSTEM_AWS_S3 = 2;
    const STORAGE_SYSTEM_AZURE = 3;


    var $settingData;

    function __construct()
    {
        $modelSetting = new Setting();
        $settingResult = $modelSetting->find()->one();
        $this->settingData = $settingResult;
       
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
        ];
    }

    public function uploadFile($mediaFile, $type, $isMultiple = false, $data = [])
    {
        $modelSetting = new Setting();
        $settingResult = $modelSetting->find()->one();

        $folderPath;
        $files = [];

        $storageSystem = $this->settingData->storage_system;
        

        if ($isMultiple) { // multiple files

        } else { // single files


            $microtime = (microtime(true) * 10000);
            $uniqueimage = $microtime . '_' . date("Ymd_His") . '_' . substr(md5($microtime), 0, 10);
            $imageName = $uniqueimage;
            $mediaFileName = $imageName . '.' . $mediaFile->extension;
            $imagePath = $mediaFile->tempName;


            if ($storageSystem == FileUpload::STORAGE_SYSTEM_AWS_S3) {

                $fileLocation = $this->getUploadedLocation($type);

                $s3Key = $this->settingData->aws_access_key_id;
                $s3Secret = $this->settingData->aws_secret_key;
                $region = $this->settingData->aws_region;
                $bucket = $this->settingData->aws_bucket;
               
                $credentials = [
                    'key'    => $s3Key,
                    'secret' => $s3Secret,
                ];
                
               

                // Create an S3 client instance
                $s3 = new S3Client([
                    'version'     => 'latest',
                    'region'      => $region,
                    'credentials' => $credentials,
                ]);
                $keyObject = './' . $fileLocation['folder'] . '/' . $mediaFileName;
                
                $result = $s3->putObject([
                    'Bucket'     => $bucket,
                    'Key'        => $keyObject,
                    'SourceFile' => $imagePath,
                    'ContentType' => $mediaFile->type
                ]);

                $fileUrl = $fileLocation['folderLocation'] . "/" . $mediaFileName;
                $fileResponse = ['file' => $mediaFileName, 'fileUrl' => $fileUrl];
              
            } else if ($storageSystem == FileUpload::STORAGE_SYSTEM_AZURE) {
                $fileLocation = $this->getUploadedLocation($type);
                
                $accountName = $this->settingData->azure_account_name;
                $accountKey = $this->settingData->azure_account_key;
                $container = $this->settingData->azure_container;

                $connectionString = "DefaultEndpointsProtocol=https;AccountName=$accountName;AccountKey=" . $accountKey;

                $client = BlobRestProxy::createBlobService($connectionString);
                $adapter = new AzureBlobStorageAdapter(
                    $client,
                    $container,
                    ''
                );
                $filesystem = new Filesystem($adapter);

                $content = fopen($imagePath, "r");


                $response = $filesystem->write($fileLocation['folder'] . '/' . $mediaFileName, $content);
                //$response = $filesystem->read($path);
                //$response = $filesystem->publicUrl($path);
                if ($response) {
                    $fileUrl = $fileLocation['folderLocation'] . "/" . $mediaFileName;
                    $fileResponse = ['file' => $mediaFileName, 'fileUrl' => $fileUrl];
                    

                }

            } else { // local storage
                $fileLocation = $this->getUploadedLocation($type);

                $imagePath = $fileLocation['folder'] . "/" . $mediaFileName;
                $fileUrl = $fileLocation['folderLocation'] . "/" . $mediaFileName;
                $mediaFile->saveAs($imagePath, false);
                $fileResponse = ['file' => $mediaFileName, 'fileUrl' => $fileUrl];

            }

        }
        
        $isProhabited=false;
        $moderationReferenceId='';
        
        
        $iscontentModerationGateway = (int)$this->settingData->content_moderation_gateway;
         
        if($iscontentModerationGateway){
            list($isProhabited, $moderationReferenceId) = Yii::$app->contentModeration->validteContent($fileUrl);
            if($isProhabited){
                $fileResponse['file']='';
                $fileResponse['fileUrl']='';
                $this->deleteFile($type,$mediaFileName);
                
            }
        }
        $fileResponse['isProhabited'] =$isProhabited;
        $fileResponse['moderationReferenceId'] =$moderationReferenceId;

        $files[] =$fileResponse;
        
        return $files;


    }
    

    public function getUploadedLocation($type)
    {
        $fileLocation = [];
        if ($type == FileUpload::TYPE_STORY || $type == FileUpload::TYPE_HIGHTLIGHT) {

            $folderName = Yii::$app->params['pathUploadStoryFolder'];
            $fileLocation = $this->getFolderLocation($folderName);

        } else if ($type == FileUpload::TYPE_COLLECTION) {

            $folderName = Yii::$app->params['pathUploadCollectionFolder'];
            $fileLocation = $this->getFolderLocation($folderName);

        } else if ($type == FileUpload::TYPE_CHAT) {
            $folderName = Yii::$app->params['pathUploadChatFolder'];
            $fileLocation = $this->getFolderLocation($folderName);


        } else if ($type == FileUpload::TYPE_AD_IMAGES) {
            $folderName = Yii::$app->params['pathUploadAdImageFolder'];
            $fileLocation = $this->getFolderLocation($folderName);


        } else if ($type == FileUpload::TYPE_USER) {

            $folderName = Yii::$app->params['pathUploadUserFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        } else if ($type == FileUpload::TYPE_POST) { // post, gallary etc

            $folderName = Yii::$app->params['pathUploadImageFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        } else if ($type == FileUpload::TYPE_COMPETITION) {
            $folderName = Yii::$app->params['pathUploadCompetitionFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        } else if ($type == FileUpload::TYPE_CATEGORY) {
            $folderName = Yii::$app->params['pathUploadCategoryFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        } else if ($type == FileUpload::TYPE_LIVE_TV) {
            $folderName = Yii::$app->params['pathUploadLiveTvFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        } else if ($type == FileUpload::TYPE_GIFT) {
            $folderName = Yii::$app->params['pathUploadGiftFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        } else if ($type == FileUpload::TYPE_VERIFICATION) {
            $folderName = Yii::$app->params['pathUploadVerificationFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        } else if ($type == FileUpload::TYPE_EVENT) {
            $folderName = Yii::$app->params['pathUploadEventFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        }else if ($type == FileUpload::TYPE_COUPON) {
            $folderName = Yii::$app->params['pathUploadCouponFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        }else if ($type == FileUpload::TYPE_EVENT_ORGANISOR) {
            $folderName = Yii::$app->params['pathUploadEventOrganisorFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        }else if ($type == FileUpload::TYPE_TV_SHOW) {
            $folderName = Yii::$app->params['pathUploadTvShowFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        }else if ($type == FileUpload::TYPE_TV_SHOW_EPISODE) {
            $folderName = Yii::$app->params['pathUploadTvShowEpisodeFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        }else if ($type == FileUpload::TYPE_REEL_AUDIO) {
            $folderName = Yii::$app->params['pathUploadReelAudioFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        }else if ($type == FileUpload::TYPE_TV_BANNER) {
            $folderName = Yii::$app->params['pathUploadTvBannerFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        }else if ($type == FileUpload::TYPE_PODCAST) {
            $folderName = Yii::$app->params['pathUploadPodcastFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        }else if ($type == FileUpload::TYPE_PODCAST_SHOW) {
            $folderName = Yii::$app->params['pathUploadPodcastShowFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        }else if ($type == FileUpload::TYPE_PODCAST_BANNER) {
            $folderName = Yii::$app->params['pathUploadPodcastBannerFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        }
        else if ($type == FileUpload::TYPE_ORGNIZATION) {
            $folderName = Yii::$app->params['pathUploadOrginationFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        }
        else if ($type == FileUpload::TYPE_CAMPAGIN) {
            $folderName = Yii::$app->params['pathUploadCampaignFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        }
        else if ($type == FileUpload::TYPE_BUSINESS) {
            $folderName = Yii::$app->params['pathUploadBusinessFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        }
        else if ($type == FileUpload::TYPE_INTEREST) {
            $folderName = Yii::$app->params['pathUploadInterestFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        }
        else if ($type == FileUpload::TYPE_JOB_APPLICATION) {
            $folderName = Yii::$app->params['pathUploadJobApplicationFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        }
        else if ($type == FileUpload::TYPE_PROMOTIONAL_AD) {
            $folderName = Yii::$app->params['pathUploadPromotionalAdFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        }
        else if ($type == FileUpload::TYPE_PROMOTIONAL_BANNER) {
            $folderName = Yii::$app->params['pathUploadPromotionalBannerFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        }
        else if ($type == FileUpload::TYPE_PICKLEBALL_COURT) {
            $folderName = Yii::$app->params['pathUploadPickleballCourtFolder'];
            $fileLocation = $this->getFolderLocation($folderName);
        }
        
        return $fileLocation;

    }
   

    public function getFileUrl($type, $fileName)
    {
        $fileLocation = $this->getUploadedLocation($type);
        return $fileLocation['folderLocation'] . "/" . $fileName;

    }

    public function getFolderLocation($folder)
    {
        $storageSystem = $this->settingData->storage_system;
        $folderPath = [];
        if ($storageSystem == FileUpload::STORAGE_SYSTEM_AWS_S3) {

            $folderPath['folder'] = $folder;
            $folderPath['folderLocation'] = $this->settingData->aws_access_url . '/' . $folder;

        } else if ($storageSystem == FileUpload::STORAGE_SYSTEM_AZURE) {
            $accountName = $this->settingData->azure_account_name;
            $container = $this->settingData->azure_container;

            $folderPath['folder'] = $folder;
            $folderPath['folderLocation'] = "https://$accountName.blob.core.windows.net/$container/" . $folder;

        } else { // local storage
            $folderPath['folder'] = Yii::getAlias('@frontend') . "/" . 'web/uploads/' . $folder;
            $folderPath['folderLocation'] = Yii::$app->params['siteUrl'] . Yii::$app->urlManagerFrontend->baseUrl . '/uploads/' . $folder;
        }
        return $folderPath;
    }

    public function deleteFile($type, $fileName)
    {
        $fileLocation = $this->getUploadedLocation($type);
        $storageSystem = $this->settingData->storage_system;
       
        if ($storageSystem == FileUpload::STORAGE_SYSTEM_AWS_S3) {
            $filename =  $fileLocation['folder'] . "/" . $fileName;
            $s3Key = $this->settingData->aws_access_key_id;
            $s3Secret = $this->settingData->aws_secret_key;
            $region = $this->settingData->aws_region;
            $bucket = $this->settingData->aws_bucket;
           
            $credentials = [
                'key'    => $s3Key,
                'secret' => $s3Secret,
            ];
            
            // Create an S3 client instance
            $s3 = new S3Client([
                'version'     => 'latest',
                'region'      => $region,
                'credentials' => $credentials,
            ]);
            $result = $s3->deleteObject([
                'Bucket' => $bucket,
                'Key'    => $filename,
            ]);
            
            //$s3 = Yii::$app->get('s3');
            //$res = $s3->commands()->delete($filename)->execute(); /// delere previous
       
        } else if ($storageSystem == FileUpload::STORAGE_SYSTEM_AZURE) {
       
        } else { // local storage

            $filename =  $fileLocation['folder'] . "/" . $fileName;
            if (file_exists($filename)) {
                if (unlink($filename)) {
                  //  echo "File deleted successfully.";
                } else {
                    //echo "Unable to delete the file.";
                }
            } else {
                //echo "File does not exist.";
            }
        }

    }








}