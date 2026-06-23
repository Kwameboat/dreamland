<?php

namespace common\helpers;

use common\models\Setting;
use Yii;

/**
 * Wasabi (S3-compatible) storage for Dreamland uploads.
 * Credentials can come from env vars (cPanel/.env) or the admin Settings → Storage screen.
 */
class DreamlandWasabiStorage
{
    public const STORAGE_FLAG = 'wasabi';

    /** Example bucket policy — replace BUCKET_NAME with your bucket. */
    public const PUBLIC_READ_POLICY_TEMPLATE = <<<'JSON'
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "DreamlandPublicRead",
      "Effect": "Allow",
      "Principal": "*",
      "Action": "s3:GetObject",
      "Resource": "arn:aws:s3:::BUCKET_NAME/*"
    }
  ]
}
JSON;

    public static function envStorageMode(): string
    {
        return strtolower(trim((string) (getenv('DREAMLAND_STORAGE') ?: '')));
    }

    public static function isEnvEnabled(): bool
    {
        return self::envStorageMode() === self::STORAGE_FLAG;
    }

    public static function applyEnvToSetting(Setting $setting): void
    {
        if (!self::isEnvEnabled()) {
            return;
        }

        $setting->storage_system = Setting::STORAGE_SYSTEM_WASABI;

        $map = [
            'wasabi_access_key' => 'WASABI_ACCESS_KEY',
            'wasabi_secret_key' => 'WASABI_SECRET_KEY',
            'wasabi_region' => 'WASABI_REGION',
            'wasabi_bucket' => 'WASABI_BUCKET',
            'wasabi_access_url' => 'WASABI_ENDPOINT',
        ];

        foreach ($map as $field => $envKey) {
            $value = getenv($envKey);
            if (is_string($value) && trim($value) !== '') {
                $setting->$field = trim($value);
            }
        }

        if (trim((string) ($setting->wasabi_access_url ?? '')) === '' && trim((string) ($setting->wasabi_region ?? '')) !== '') {
            $setting->wasabi_access_url = self::defaultEndpointForRegion((string) $setting->wasabi_region);
        }
    }

    public static function isConfigured(?Setting $setting = null): bool
    {
        $setting = $setting ?: self::settingSnapshot();
        if ((int) ($setting->storage_system ?? 0) !== Setting::STORAGE_SYSTEM_WASABI) {
            return false;
        }

        return trim((string) ($setting->wasabi_access_key ?? '')) !== ''
            && trim((string) ($setting->wasabi_secret_key ?? '')) !== ''
            && trim((string) ($setting->wasabi_bucket ?? '')) !== ''
            && trim((string) ($setting->wasabi_region ?? '')) !== '';
    }

    public static function settingSnapshot(): Setting
    {
        $setting = null;
        try {
            $setting = Setting::find()->orderBy(['id' => SORT_DESC])->one();
        } catch (\Throwable $e) {
            Yii::warning($e->getMessage(), __METHOD__);
        }
        if (!$setting) {
            $setting = new Setting();
        }
        self::applyEnvToSetting($setting);
        return $setting;
    }

    public static function defaultEndpointForRegion(string $region): string
    {
        $region = trim($region) ?: 'us-east-1';
        return 'https://s3.' . $region . '.wasabisys.com';
    }

    public static function normalizeEndpoint(?string $endpoint, ?string $region = null): string
    {
        $endpoint = rtrim(trim((string) $endpoint), '/');
        if ($endpoint !== '') {
            return $endpoint;
        }
        return self::defaultEndpointForRegion((string) $region);
    }

    /** Public URL base for a folder, e.g. …/dreamland-media/image */
    public static function publicFolderUrl(string $folder, ?Setting $setting = null): string
    {
        $setting = $setting ?: self::settingSnapshot();
        $publicBase = trim((string) (getenv('DREAMLAND_WASABI_PUBLIC_URL') ?: ''));
        if ($publicBase === '') {
            $endpoint = self::normalizeEndpoint($setting->wasabi_access_url ?? '', $setting->wasabi_region ?? '');
            $bucket = trim((string) ($setting->wasabi_bucket ?? ''));
            $publicBase = $endpoint . '/' . $bucket;
        }
        $publicBase = rtrim($publicBase, '/');
        $folder = trim($folder, '/');
        return $publicBase . '/' . $folder;
    }

    /** Base URL the PWA uses when building media paths (image folder). */
    public static function uploadsBaseForApi(?Setting $setting = null): string
    {
        if (!self::isConfigured($setting)) {
            return \common\helpers\DreamlandMediaUrl::localPublicUploadsBase(
                (string) (Yii::$app->params['pathUploadImageFolder'] ?? 'image')
            );
        }

        $folder = Yii::$app->params['pathUploadImageFolder'] ?? 'image';
        return self::publicFolderUrl((string) $folder, $setting);
    }

    public static function objectKey(string $folder, string $fileName): string
    {
        return trim($folder, '/') . '/' . ltrim($fileName, '/');
    }

    public static function createClient(?Setting $setting = null)
    {
        if (!class_exists('Aws\\S3\\S3Client')) {
            throw new \RuntimeException('AWS SDK missing. Run: composer require aws/aws-sdk-php');
        }

        $setting = $setting ?: self::settingSnapshot();
        $region = trim((string) ($setting->wasabi_region ?? 'us-east-1'));
        $endpoint = self::normalizeEndpoint($setting->wasabi_access_url ?? '', $region);

        return new \Aws\S3\S3Client([
            'version' => 'latest',
            'region' => $region,
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => filter_var(getenv('WASABI_PATH_STYLE') ?: '1', FILTER_VALIDATE_BOOLEAN),
            'credentials' => [
                'key' => (string) $setting->wasabi_access_key,
                'secret' => (string) $setting->wasabi_secret_key,
            ],
        ]);
    }

    public static function putLocalFile(
        string $folder,
        string $fileName,
        string $localPath,
        ?string $contentType = null,
        ?Setting $setting = null
    ): void {
        $setting = $setting ?: self::settingSnapshot();
        $client = self::createClient($setting);
        $bucket = (string) $setting->wasabi_bucket;
        $key = self::objectKey($folder, $fileName);

        $params = [
            'Bucket' => $bucket,
            'Key' => $key,
            'SourceFile' => $localPath,
            'ContentType' => $contentType ?: 'application/octet-stream',
        ];

        try {
            $client->putObject($params + ['ACL' => 'public-read']);
        } catch (\Throwable $e) {
            Yii::warning('Wasabi ACL public-read skipped: ' . $e->getMessage(), __METHOD__);
            $client->putObject($params);
        }
    }

    public static function deleteObject(string $folder, string $fileName, ?Setting $setting = null): void
    {
        $setting = $setting ?: self::settingSnapshot();
        $client = self::createClient($setting);
        $client->deleteObject([
            'Bucket' => (string) $setting->wasabi_bucket,
            'Key' => self::objectKey($folder, $fileName),
        ]);
    }

    /** @return array{ok:bool,message:string,bucket?:string,region?:string,endpoint?:string} */
    public static function testConnection(?Setting $setting = null): array
    {
        if (!self::isConfigured($setting)) {
            return ['ok' => false, 'message' => 'Wasabi is not configured. Set admin Storage settings or WASABI_* env vars.'];
        }

        $setting = $setting ?: self::settingSnapshot();
        try {
            $client = self::createClient($setting);
            $client->headBucket(['Bucket' => (string) $setting->wasabi_bucket]);
            return [
                'ok' => true,
                'message' => 'Wasabi bucket reachable.',
                'bucket' => (string) $setting->wasabi_bucket,
                'region' => (string) $setting->wasabi_region,
                'endpoint' => self::normalizeEndpoint($setting->wasabi_access_url ?? '', $setting->wasabi_region ?? ''),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Wasabi connection failed: ' . $e->getMessage()];
        }
    }
}
