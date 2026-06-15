<?php
namespace common\models;

use Yii;
use yii\helpers\ArrayHelper;

//// also synk this api/model to common/module

use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use League\Flysystem\Filesystem;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

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
    const TYPE_CAR_RIDE =27;
    const TYPE_JOB_APPLICATION =28; 
    const TYPE_PROMOTIONAL_AD =29;
    const TYPE_PROMOTIONAL_BANNER =30;

    const STORAGE_SYSTEM_LOCAL = 1;
    const STORAGE_SYSTEM_AWS_S3 = 2;
    const STORAGE_SYSTEM_AZURE = 3;




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
       
        $sourceType= @(int)$data['type'];

        $folderPath;
        $files = [];

        $storageSystem = Yii::$app->params['storageSystem'];


        if ($isMultiple) { // multiple files

        } else { // single files


            $microtime = (microtime(true) * 10000);
            $uniqueimage = $microtime . '_' . date("Ymd_His") . '_' . substr(md5($microtime), 0, 10);
            $imageName = $uniqueimage;
            if($sourceType==2){ //copy file
                $sourceFileLocation =   @$data['sourceFileLocation'];
                $imagePath = $sourceFileLocation;
                $mediaFileName = $imageName.'.jpg';

            }else{ //upload
                $mediaFileName = $imageName . '.' . $mediaFile->extension;
                $imagePath = $mediaFile->tempName;
               
            }


            //$mediaFileName = $imageName . '.' . $mediaFile->extension;
            //$imagePath = $mediaFile->tempName;
           


            if ($storageSystem == FileUpload::STORAGE_SYSTEM_AWS_S3) {

                $fileLocation = $this->getUploadedLocation($type);


                $s3 = Yii::$app->get('s3');
                if($sourceType==2){ //copy file
                    $result = $s3->upload('./' . $fileLocation['folder'] . '/' . $mediaFileName, $imagePath);
                }else{
                    $result = $s3->commands()->upload('./' . $fileLocation['folder'] . '/' . $mediaFileName, $imagePath)->withContentType($mediaFile->type)->execute();
                }
                $fileUrl = $fileLocation['folderLocation'] . "/" . $mediaFileName;
                $files[] = ['file' => $mediaFileName, 'fileUrl' => $fileUrl];



            } else if ($storageSystem == FileUpload::STORAGE_SYSTEM_AZURE) {
                $fileLocation = $this->getUploadedLocation($type);
                $accountName = Yii::$app->params['azureFs']['accountName'];
                $accountKey = Yii::$app->params['azureFs']['accountKey'];
                $container = Yii::$app->params['azureFs']['container'];

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
                    $files[] = ['file' => $mediaFileName, 'fileUrl' => $fileUrl];

                }

            } else { // local storage
                $fileLocation = $this->getUploadedLocation($type);

                $imagePath = $fileLocation['folder'] . "/" . $mediaFileName;
                $fileUrl = $fileLocation['folderLocation'] . "/" . $mediaFileName;
                if($sourceType==2){ //copy file
                    $sourceFileLocation =   @$data['sourceFileLocation'];
                    copy($sourceFileLocation,$imagePath);

                }else{ //upload
                    $mediaFile->saveAs($imagePath, false);
                }
                $files[] = ['file' => $mediaFileName, 'fileUrl' => $fileUrl];

            }



        }
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
        else if ($type == FileUpload::TYPE_CAR_RIDE) {
            $folderName = Yii::$app->params['pathUploadCarrideFolder'];
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
        
        
        return $fileLocation;

    }

    public function getFileUrl($type, $fileName)
    {
        $fileLocation = $this->getUploadedLocation($type);
        return $fileLocation['folderLocation'] . "/" . $fileName;

    }

    public function getFolderLocation($folder)
    {
        $storageSystem = Yii::$app->params['storageSystem'];
        $folderPath = [];
        if ($storageSystem == FileUpload::STORAGE_SYSTEM_AWS_S3) {

            $folderPath['folder'] = $folder;
            $folderPath['folderLocation'] = Yii::$app->params['s3']['storageUrl'] . '/' . $folder;

        } else if ($storageSystem == FileUpload::STORAGE_SYSTEM_AZURE) {
            $accountName = Yii::$app->params['azureFs']['accountName'];
            $container = Yii::$app->params['azureFs']['container'];
            $folderPath['folder'] = $folder;
            $folderPath['folderLocation'] = "https://$accountName.blob.core.windows.net/$container/" . $folder;

        } else { // local storage
            $folderPath['folder'] = Yii::getAlias('@frontend') . "/" . 'web/uploads/' . $folder;
            $folderPath['folderLocation'] = Yii::$app->params['siteUrl'] . Yii::$app->urlManagerFrontend->baseUrl . '/uploads/' . $folder;
        }
        return $folderPath;
    }









}