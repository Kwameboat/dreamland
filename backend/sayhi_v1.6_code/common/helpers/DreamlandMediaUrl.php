<?php

namespace common\helpers;

use api\modules\v1\models\PostGallary;
use common\components\FileUpload;
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

    /**
     * @param \api\modules\v1\models\Post|\common\models\Post|int $post
     */
    public static function resolvePostVideoUrl($post): ?string
    {
        if (is_numeric($post)) {
            $post = \api\modules\v1\models\Post::findOne((int) $post);
        }
        if (!$post) {
            return null;
        }

        foreach (self::filenameCandidatesForPost($post) as $filename) {
            $url = self::fileUrlForPostFilename($filename);
            if ($url !== '') {
                return $url;
            }
        }

        return null;
    }

    /**
     * @param \api\modules\v1\models\Post|\common\models\Post $post
     * @return string[]
     */
    public static function filenameCandidatesForPost($post): array
    {
        $names = [];
        $postId = (int) $post->id;

        $galleries = PostGallary::find()
            ->where(['post_id' => $postId])
            ->andWhere(['media_type' => PostGallary::MEDIA_TYPE_VIDEO])
            ->orderBy(['is_default' => SORT_DESC, 'id' => SORT_ASC])
            ->all();

        foreach ($galleries as $gallery) {
            if (!empty($gallery->filename)) {
                $names[] = (string) $gallery->filename;
            }
        }

        foreach ([$post->title ?? '', $post->description ?? '', $post->image ?? ''] as $value) {
            $text = trim((string) $value);
            if ($text === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $text)) {
                continue;
            }
            $names[] = $text;
            if (!preg_match('/\.[A-Za-z0-9]{2,5}$/', $text)) {
                foreach (['mp4', 'mov', 'webm', 'm4v'] as $ext) {
                    $names[] = $text . '.' . $ext;
                }
            }
        }

        $names = array_values(array_unique(array_filter($names)));
        $existing = [];
        foreach ($names as $name) {
            if (self::localFileExists($name) || DreamlandWasabiStorage::isConfigured()) {
                $existing[] = $name;
            }
        }

        return $existing !== [] ? $existing : $names;
    }

    public static function fileUrlForPostFilename(string $filename): string
    {
        $filename = ltrim(trim($filename), '/');
        if ($filename === '') {
            return '';
        }

        if (self::localFileExists($filename)) {
            return self::localPublicUploadsBase('image') . '/' . $filename;
        }

        if (Yii::$app->has('fileUpload')) {
            return (string) Yii::$app->fileUpload->getFileUrl(FileUpload::TYPE_POST, $filename);
        }

        return self::localPublicUploadsBase('image') . '/' . $filename;
    }

    public static function mediaFileReachable(string $filename): bool
    {
        $filename = ltrim(basename($filename), '/');
        if ($filename === '') {
            return false;
        }

        if (self::localFileExists($filename)) {
            return true;
        }

        $folder = (string) (Yii::$app->params['pathUploadImageFolder'] ?? 'image');
        if (DreamlandWasabiStorage::isConfigured()) {
            return DreamlandWasabiStorage::objectExists($folder, $filename);
        }

        return false;
    }

    public static function localFileExists(string $filename): bool
    {
        $filename = ltrim(basename($filename), '/');
        if ($filename === '') {
            return false;
        }

        foreach (self::localUploadDirs() as $dir) {
            if (is_file($dir . DIRECTORY_SEPARATOR . $filename)) {
                return true;
            }
        }

        return false;
    }

    /** @return string[] */
    public static function localUploadDirs(): array
    {
        $dirs = [];
        $override = getenv('DREAMLAND_UPLOAD_DIR');
        if (is_string($override) && $override !== '') {
            $dirs[] = rtrim($override, '/\\') . '/image';
        }

        foreach (['@api/runtime/uploads/image', '@frontend/web/uploads/image'] as $alias) {
            try {
                $dirs[] = Yii::getAlias($alias);
            } catch (\Throwable $e) {
                // ignore missing alias
            }
        }

        return array_values(array_unique($dirs));
    }
}
