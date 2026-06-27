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
    public static function cdnBase(): ?string
    {
        foreach (['DREAMLAND_CDN_BASE_URL', 'DREAMLAND_UPLOADS_URL', 'DREAMLAND_WASABI_PUBLIC_URL'] as $key) {
            $value = getenv($key);
            if (!is_string($value) || trim($value) === '') {
                continue;
            }
            $base = rtrim(trim($value), '/');
            if ($key === 'DREAMLAND_UPLOADS_URL' && substr($base, -6) === '/image') {
                $base = substr($base, 0, -6);
            }
            return $base;
        }

        return null;
    }

    public static function applyCdn(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return $url;
        }
        $cdn = self::cdnBase();
        if ($cdn === null) {
            return $url;
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['path'])) {
            return $url;
        }

        $path = $parts['path'];
        if (str_contains($path, '/uploads/')) {
            $suffix = substr($path, strpos($path, '/uploads/') + strlen('/uploads/'));
            return $cdn . '/' . ltrim($suffix, '/');
        }
        if (preg_match('#/(image|hls)/#', $path)) {
            return $cdn . $path;
        }

        return $url;
    }

    public static function folderPublicBase(string $folder): string
    {
        $folder = trim($folder, '/');
        $cdn = self::cdnBase();
        if ($cdn !== null) {
            return $cdn . '/' . $folder;
        }

        if ($folder === 'image' || $folder === '') {
            return self::publicUploadsBase('image');
        }

        if (!DreamlandStorageMode::useLocalDisk() && DreamlandWasabiStorage::isConfigured()) {
            return DreamlandWasabiStorage::publicFolderUrl($folder);
        }

        return self::siteBase() . self::apiPublicPath() . '/frontend/web/uploads/' . $folder;
    }

    public static function fileUrlForFolderFilename(string $folder, string $filename): string
    {
        $filename = ltrim(trim($filename), '/');
        if ($filename === '') {
            return '';
        }

        return self::applyCdn(self::folderPublicBase($folder) . '/' . $filename) ?? '';
    }

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
        if (!DreamlandStorageMode::useLocalDisk() && DreamlandWasabiStorage::isConfigured()) {
            return DreamlandWasabiStorage::publicFolderUrl((string) $folder);
        }

        return self::localPublicUploadsBase((string) $folder);
    }

    public static function mediaApiReelUrl(string $filename): string
    {
        $filename = ltrim(basename($filename), '/');
        return self::siteBase() . self::apiPublicPath() . '/v1/media/reel?name=' . rawurlencode($filename);
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

        $gallery = self::defaultVideoGallery($post);
        if ($gallery && !empty($gallery->optimized_filename)) {
            $optimized = self::fileUrlForPostFilename((string) $gallery->optimized_filename);
            if ($optimized !== '') {
                return $optimized;
            }
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
     * @param \api\modules\v1\models\Post|\common\models\Post|int $post
     */
    public static function resolvePostHlsUrl($post): ?string
    {
        if (is_numeric($post)) {
            $post = \api\modules\v1\models\Post::findOne((int) $post);
        }
        $gallery = self::defaultVideoGallery($post);
        if (!$gallery || empty($gallery->hls_playlist)) {
            return null;
        }

        $playlist = ltrim((string) $gallery->hls_playlist, '/');
        $folder = dirname($playlist);
        $file = basename($playlist);
        if ($folder === '.' || $folder === '') {
            return self::fileUrlForFolderFilename('hls', $file);
        }

        return self::fileUrlForFolderFilename($folder, $file);
    }

    /**
     * @param \api\modules\v1\models\Post|\common\models\Post|int $post
     */
    public static function resolvePostPosterUrl($post): ?string
    {
        if (is_numeric($post)) {
            $post = \api\modules\v1\models\Post::findOne((int) $post);
        }
        $gallery = self::defaultVideoGallery($post);
        if (!$gallery || empty($gallery->video_thumb)) {
            return null;
        }

        return self::fileUrlForPostFilename((string) $gallery->video_thumb);
    }

    /**
     * @param \api\modules\v1\models\Post|\common\models\Post|null $post
     */
    public static function defaultVideoGallery($post): ?PostGallary
    {
        if (!$post) {
            return null;
        }

        return PostGallary::find()
            ->where(['post_id' => (int) $post->id, 'media_type' => PostGallary::MEDIA_TYPE_VIDEO])
            ->orderBy(['is_default' => SORT_DESC, 'id' => SORT_ASC])
            ->one();
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
            if (!empty($gallery->optimized_filename)) {
                $names[] = (string) $gallery->optimized_filename;
            }
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
            return self::applyCdn(self::localPublicUploadsBase('image') . '/' . $filename) ?? '';
        }

        if (Yii::$app->has('fileUpload')) {
            return self::applyCdn((string) Yii::$app->fileUpload->getFileUrl(FileUpload::TYPE_POST, $filename)) ?? '';
        }

        return self::applyCdn(self::localPublicUploadsBase('image') . '/' . $filename) ?? '';
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

        if (DreamlandStorageMode::useLocalDisk()) {
            return false;
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
