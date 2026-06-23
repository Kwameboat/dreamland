<?php

namespace common\helpers;

use Yii;

/**
 * Public URLs for uploaded media (local disk vs Wasabi).
 */
class DreamlandMediaUrl
{
    public static function apiPublicPath(): string
    {
        $fromEnv = getenv('DREAMLAND_API_PUBLIC_PATH');
        if (is_string($fromEnv) && trim($fromEnv) !== '') {
            return '/' . trim($fromEnv, '/');
        }

        $fromParams = Yii::$app->params['dreamlandApiPublicPath'] ?? '/api';
        return '/' . trim((string) $fromParams, '/');
    }

    public static function siteBase(): string
    {
        return rtrim((string) (Yii::$app->params['siteUrl'] ?? 'https://dreamlandgh.app'), '/');
    }

    /** Local uploads served through the API router on cPanel. */
    public static function localPublicUploadsBase(string $folder = 'image'): string
    {
        $folder = trim($folder, '/');

        return self::siteBase() . self::apiPublicPath() . '/frontend/web/uploads/' . $folder;
    }

    public static function publicUploadsBase(?string $folder = null): string
    {
        $folder = $folder ?? (Yii::$app->params['pathUploadImageFolder'] ?? 'image');
        if (DreamlandWasabiStorage::isConfigured()) {
            return DreamlandWasabiStorage::publicFolderUrl((string) $folder);
        }

        return self::localPublicUploadsBase((string) $folder);
    }
}
