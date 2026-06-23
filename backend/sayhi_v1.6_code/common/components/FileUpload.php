<?php
namespace common\components;

use Yii;
use yii\base\Component;
use common\models\Setting;
use common\models\User;
use common\helpers\DreamlandWasabiStorage;

use AzureOss\FlysystemAzureBlobStorage\AzureBlobStorageAdapter;
use AzureOss\Storage\Blob\BlobServiceClient;
use League\Flysystem\Filesystem;

use Aws\S3\S3Client;

class FileUpload extends Component

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
    const STORAGE_SYSTEM_WASABI = 4;

    


    var $settingData;

    function __construct()
    {
        $modelSetting = new Setting();
        $this->settingData = $modelSetting->getSettingData();
        if (!$this->settingData) {
            throw new \RuntimeException('Application settings row is missing.');
        }
    }

    private function localDiskRoot(): string
    {
        $override = getenv('DREAMLAND_UPLOAD_DIR');
        if (is_string($override) && $override !== '') {
            return rtrim($override, '/\\');
        }
        // api/runtime is writable on Render/Docker; frontend/web/uploads often is not.
        return Yii::getAlias('@api/runtime/uploads');
    }

    private function effectiveStorageSystem(): int
    {
        if (getenv('DREAMLAND_FORCE_LOCAL_UPLOADS') === '1' && !DreamlandWasabiStorage::isEnvEnabled()) {
            return self::STORAGE_SYSTEM_LOCAL;
        }

        if (DreamlandWasabiStorage::isConfigured($this->settingData)) {
            return self::STORAGE_SYSTEM_WASABI;
        }

        $storage = (int) ($this->settingData->storage_system ?? self::STORAGE_SYSTEM_LOCAL);
        if ($storage === self::STORAGE_SYSTEM_AWS_S3) {
            if (trim((string) ($this->settingData->aws_access_key_id ?? '')) === ''
                || trim((string) ($this->settingData->aws_bucket ?? '')) === '') {
                return self::STORAGE_SYSTEM_LOCAL;
            }
        } elseif ($storage === self::STORAGE_SYSTEM_WASABI) {
            if (trim((string) ($this->settingData->wasabi_access_key ?? '')) === ''
                || trim((string) ($this->settingData->wasabi_bucket ?? '')) === '') {
                return self::STORAGE_SYSTEM_LOCAL;
            }
        } elseif ($storage === self::STORAGE_SYSTEM_AZURE) {
            if (trim((string) ($this->settingData->azure_account_name ?? '')) === ''
                || trim((string) ($this->settingData->azure_container ?? '')) === '') {
                return self::STORAGE_SYSTEM_LOCAL;
            }
        }

        return $storage;
    }

    public function isLocalDiskWritable(): bool
    {
        try {
            $dir = $this->localDiskRoot() . '/user';
            $this->ensureUploadDir($dir);
            return is_writable($dir);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function publicUrlForFolder(string $folder): string
    {
        return \common\helpers\DreamlandMediaUrl::localPublicUploadsBase($folder);
    }

    private function ensureUploadDir(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }
        if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Upload folder is not writable: ' . $dir);
        }
    }

    private function storeOnLocalDisk(int $type, $mediaFile, string $mediaFileName, $fileType): array
    {
        $fileLocation = $this->getUploadedLocation($type);
        $imagePath = $fileLocation['folder'] . '/' . $mediaFileName;
        $this->ensureUploadDir($fileLocation['folder']);
        if (!$mediaFile->saveAs($imagePath, false)) {
            throw new \RuntimeException('Could not write uploaded file to disk: ' . $imagePath);
        }
        $fileUrl = $fileLocation['folderLocation'] . '/' . $mediaFileName;
        return ['file' => $mediaFileName, 'fileUrl' => $fileUrl, 'fileType' => $fileType];
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
        if ($mediaFile && strpos((string) ($mediaFile->type ?? ''), 'video') === 0) {
            @set_time_limit(600);
        }

        $folderPath;
        $files = [];

        $storageSystem = $this->effectiveStorageSystem();
              

        if ($isMultiple) { // multiple files

        } else { // single files


            $microtime = (microtime(true) * 10000);
            $uniqueimage = $microtime . '_' . date("Ymd_His") . '_' . substr(md5($microtime), 0, 10);
            $imageName = $uniqueimage;
            $mediaFileName = $imageName . '.' . $mediaFile->extension;
            $imagePath = $mediaFile->tempName;
            $fileType = explode('/', $mediaFile->type )[0];
            //$fileType=1;
            if($fileType=='image'){
                $fileType=1;
            }else if($fileType=='video'){
                $fileType=2;
            }else if($fileType=='audio'){
                $fileType=3;
            }



            $fileResponse = null;
            try {
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
                
                $s3->putObject([
                    'Bucket'     => $bucket,
                    'Key'        => $keyObject,
                    'SourceFile' => $imagePath,
                    'ContentType' => $mediaFile->type
                ]);

                $fileUrl = $fileLocation['folderLocation'] . "/" . $mediaFileName;
                $fileResponse = ['file' => $mediaFileName, 'fileUrl' => $fileUrl,'fileType'=>$fileType];
              
            } else if ($storageSystem == FileUpload::STORAGE_SYSTEM_WASABI) {

                $fileLocation = $this->getUploadedLocation($type);
                DreamlandWasabiStorage::putLocalFile(
                    (string) $fileLocation['folder'],
                    $mediaFileName,
                    $imagePath,
                    (string) ($mediaFile->type ?: 'application/octet-stream'),
                    $this->settingData
                );

                $fileUrl = $fileLocation['folderLocation'] . '/' . $mediaFileName;
                $fileResponse = ['file' => $mediaFileName, 'fileUrl' => $fileUrl, 'fileType' => $fileType];
              
            } else if ($storageSystem == FileUpload::STORAGE_SYSTEM_AZURE) {
                $fileLocation = $this->getUploadedLocation($type);
                
                $accountName = $this->settingData->azure_account_name;
                $accountKey = $this->settingData->azure_account_key;
                $container = $this->settingData->azure_container;

                $connectionString = "DefaultEndpointsProtocol=https;AccountName=$accountName;AccountKey=$accountKey;EndpointSuffix=core.windows.net";

                $blobServiceClient = BlobServiceClient::fromConnectionString($connectionString);
                $client = $blobServiceClient->getContainerClient($container);

                $adapter = new AzureBlobStorageAdapter($client);
                $filesystem = new Filesystem($adapter);
                $content = file_get_contents($imagePath); 
                $response = $filesystem->write($fileLocation['folder'] . '/' . $mediaFileName, $content);

                if ($response) {
                    $fileUrl = $fileLocation['folderLocation'] . "/" . $mediaFileName;
                    $fileResponse = ['file' => $mediaFileName, 'fileUrl' => $fileUrl,'fileType'=>$fileType];
                }

            } else { // local storage
                $fileResponse = $this->storeOnLocalDisk($type, $mediaFile, $mediaFileName, $fileType);
            }
            } catch (\Throwable $e) {
                if (DreamlandWasabiStorage::isEnvEnabled()) {
                    throw new \RuntimeException('Wasabi upload failed: ' . $e->getMessage(), 0, $e);
                }
                Yii::warning('Primary upload storage failed: ' . $e->getMessage(), __METHOD__);
                $fileResponse = $this->storeOnLocalDisk($type, $mediaFile, $mediaFileName, $fileType);
            }

            if (empty($fileResponse['file'])) {
                throw new \RuntimeException('Upload could not be saved to disk.');
            }

        }
        $isProhabited=false;
        $moderationReferenceId='';
        
        $skipContentModeration = !empty($data['skipContentModeration']);
        $iscontentModerationGateway = $skipContentModeration
            ? 0
            : (int)($this->settingData->content_moderation_gateway ?? 0);

       if(@Yii::$app->user->identity->role){
            if(Yii::$app->user->identity->role==User::ROLE_ADMIN || Yii::$app->user->identity->role==User::ROLE_SUBADMIN){
                $iscontentModerationGateway=false;
            }
        }

         
        if($iscontentModerationGateway){
            try {
                $moderationResult = Yii::$app->contentModeration->validteContent($fileResponse['fileUrl'] ?? '');
                if (is_array($moderationResult) && count($moderationResult) >= 2) {
                    list($isProhabited, $moderationReferenceId) = $moderationResult;
                }
            } catch (\Throwable $e) {
                Yii::warning('Content moderation skipped: ' . $e->getMessage(), __METHOD__);
            }
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
         $storageSystem = $this->effectiveStorageSystem();
        $folderPath = [];
        if ($storageSystem == FileUpload::STORAGE_SYSTEM_AWS_S3) {

            $folderPath['folder'] = $folder;
            $folderPath['folderLocation'] = $this->settingData->aws_access_url . '/' . $folder;

        } else if ($storageSystem == FileUpload::STORAGE_SYSTEM_WASABI) {

            $folderPath['folder'] = $folder;
            $folderPath['folderLocation'] = DreamlandWasabiStorage::publicFolderUrl($folder, $this->settingData);
        } else if ($storageSystem == FileUpload::STORAGE_SYSTEM_AZURE) {
            $accountName = $this->settingData->azure_account_name;
            $container = $this->settingData->azure_container;

            $folderPath['folder'] = $folder;
            $folderPath['folderLocation'] = "https://$accountName.blob.core.windows.net/$container/" . $folder;

        }
         else { // local storage
            $folderPath['folder'] = $this->localDiskRoot() . '/' . $folder;
            $folderPath['folderLocation'] = $this->publicUrlForFolder($folder);
        }
        return $folderPath;
    }

    public function deleteFile($type, $fileName)
    {
        $fileLocation = $this->getUploadedLocation($type);
        $storageSystem = $this->effectiveStorageSystem();
       
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
       
        } else if ($storageSystem == FileUpload::STORAGE_SYSTEM_WASABI) {
            try {
                DreamlandWasabiStorage::deleteObject((string) $fileLocation['folder'], $fileName, $this->settingData);
            } catch (\Throwable $e) {
                Yii::warning('Wasabi delete failed: ' . $e->getMessage(), __METHOD__);
            }
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
?>