<?php

namespace common\components;

use common\helpers\DreamlandMediaUrl;
use common\helpers\DreamlandStorageMode;
use common\helpers\DreamlandWasabiStorage;
use Yii;

/**
 * FFmpeg transcode pipeline: poster JPEG, 720p faststart MP4, optional HLS.
 * Requires ffmpeg on PATH or DREAMLAND_FFMPEG_PATH.
 */
class DreamlandVideoProcessor
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_READY = 'ready';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    public static function isEnabled(): bool
    {
        $flag = getenv('DREAMLAND_TRANSCODE_ENABLED');
        if ($flag === '0' || $flag === 'false') {
            return false;
        }

        return self::resolveFfmpegBinary() !== null;
    }

    public static function resolveFfmpegBinary(): ?string
    {
        $custom = getenv('DREAMLAND_FFMPEG_PATH');
        if (is_string($custom) && $custom !== '' && is_executable($custom)) {
            return $custom;
        }

        foreach (['ffmpeg', '/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg'] as $candidate) {
            if ($candidate === 'ffmpeg') {
                $out = [];
                $code = 0;
                @exec('command -v ffmpeg 2>/dev/null', $out, $code);
                if ($code === 0 && !empty($out[0]) && is_executable($out[0])) {
                    return $out[0];
                }
                continue;
            }
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array{poster:?string,optimized:?string,hls_playlist:?string,status:string,width:int,height:int}
     */
    public static function processUploadedReel(string $sourceFilename, int $postId): array
    {
        $result = [
            'poster' => null,
            'optimized' => null,
            'hls_playlist' => null,
            'status' => self::STATUS_SKIPPED,
            'width' => 0,
            'height' => 0,
        ];

        if (!self::isEnabled()) {
            return $result;
        }

        $sourcePath = self::resolveLocalSourcePath($sourceFilename);
        if ($sourcePath === null || !is_file($sourcePath)) {
            $result['status'] = self::STATUS_FAILED;
            Yii::warning("Transcode source missing: {$sourceFilename}", __METHOD__);
            return $result;
        }

        $ffmpeg = self::resolveFfmpegBinary();
        if ($ffmpeg === null) {
            return $result;
        }

        $base = pathinfo($sourceFilename, PATHINFO_FILENAME);
        $tmpDir = Yii::getAlias('@runtime/transcode/' . $postId . '_' . uniqid('', true));
        if (!is_dir($tmpDir) && !@mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
            $result['status'] = self::STATUS_FAILED;
            return $result;
        }

        try {
            $posterName = $base . '_poster.jpg';
            $optimizedName = $base . '_720.mp4';
            $posterPath = $tmpDir . DIRECTORY_SEPARATOR . 'poster.jpg';
            $optimizedPath = $tmpDir . DIRECTORY_SEPARATOR . 'optimized.mp4';
            $hlsDir = $tmpDir . DIRECTORY_SEPARATOR . 'hls';
            @mkdir($hlsDir, 0775, true);

            $scale = 'scale=min(720\\,iw):min(1280\\,ih):force_original_aspect_ratio=decrease';

            self::runFfmpeg($ffmpeg, [
                '-y', '-ss', '1', '-i', $sourcePath,
                '-vframes', '1', '-q:v', '3',
                $posterPath,
            ]);

            self::runFfmpeg($ffmpeg, [
                '-y', '-i', $sourcePath,
                '-vf', $scale,
                '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '23',
                '-movflags', '+faststart',
                '-c:a', 'aac', '-b:a', '128k', '-ac', '2',
                $optimizedPath,
            ]);

            $hlsPlaylistRel = null;
            if (filter_var(getenv('DREAMLAND_HLS_ENABLED') ?: '1', FILTER_VALIDATE_BOOLEAN)) {
                $segmentPattern = $hlsDir . DIRECTORY_SEPARATOR . 'seg%03d.ts';
                $playlistPath = $hlsDir . DIRECTORY_SEPARATOR . 'master.m3u8';
                self::runFfmpeg($ffmpeg, [
                    '-y', '-i', $sourcePath,
                    '-vf', $scale,
                    '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '23',
                    '-c:a', 'aac', '-b:a', '128k', '-ac', '2',
                    '-hls_time', '4', '-hls_playlist_type', 'vod',
                    '-hls_segment_filename', $segmentPattern,
                    $playlistPath,
                ]);
                if (is_file($playlistPath)) {
                    $hlsPlaylistRel = self::publishHlsDirectory($hlsDir, $postId);
                }
            }

            if (is_file($posterPath)) {
                $result['poster'] = self::publishArtifact($posterPath, $posterName);
            }
            if (is_file($optimizedPath)) {
                $result['optimized'] = self::publishArtifact($optimizedPath, $optimizedName);
                [$result['width'], $result['height']] = self::probeDimensions($optimizedPath);
            }
            $result['hls_playlist'] = $hlsPlaylistRel;
            $result['status'] = ($result['optimized'] || $result['poster']) ? self::STATUS_READY : self::STATUS_FAILED;
        } catch (\Throwable $e) {
            $result['status'] = self::STATUS_FAILED;
            Yii::error('Reel transcode failed: ' . $e->getMessage(), __METHOD__);
        } finally {
            self::removeDirectory($tmpDir);
        }

        return $result;
    }

    public static function resolveLocalSourcePath(string $filename): ?string
    {
        $filename = ltrim(basename($filename), '/');
        if ($filename === '') {
            return null;
        }

        foreach (DreamlandMediaUrl::localUploadDirs() as $dir) {
            $path = $dir . DIRECTORY_SEPARATOR . $filename;
            if (is_file($path)) {
                return $path;
            }
        }

        if (!DreamlandStorageMode::useLocalDisk() && DreamlandWasabiStorage::isConfigured()) {
            $folder = (string) (Yii::$app->params['pathUploadImageFolder'] ?? 'image');
            $tmp = Yii::getAlias('@runtime/transcode-src');
            if (!is_dir($tmp)) {
                @mkdir($tmp, 0775, true);
            }
            $local = $tmp . DIRECTORY_SEPARATOR . $filename;
            if (!is_file($local)) {
                try {
                    $client = DreamlandWasabiStorage::createClient();
                    $setting = DreamlandWasabiStorage::settingSnapshot();
                    $client->getObject([
                        'Bucket' => (string) $setting->wasabi_bucket,
                        'Key' => DreamlandWasabiStorage::objectKey($folder, $filename),
                        'SaveAs' => $local,
                    ]);
                } catch (\Throwable $e) {
                    Yii::warning('Could not download Wasabi source for transcode: ' . $e->getMessage(), __METHOD__);
                    return null;
                }
            }
            return is_file($local) ? $local : null;
        }

        return null;
    }

    private static function publishArtifact(string $localPath, string $targetName): ?string
    {
        $folder = (string) (Yii::$app->params['pathUploadImageFolder'] ?? 'image');
        if (DreamlandStorageMode::useLocalDisk()) {
            foreach (DreamlandMediaUrl::localUploadDirs() as $dir) {
                if (!is_dir($dir)) {
                    @mkdir($dir, 0775, true);
                }
                $dest = $dir . DIRECTORY_SEPARATOR . $targetName;
                if (@copy($localPath, $dest)) {
                    return $targetName;
                }
            }
            return null;
        }

        if (DreamlandWasabiStorage::isConfigured()) {
            $mime = self::guessMime($targetName);
            DreamlandWasabiStorage::putLocalFile($folder, $targetName, $localPath, $mime);
            return $targetName;
        }

        return null;
    }

    private static function publishHlsDirectory(string $localHlsDir, int $postId): ?string
    {
        $folder = 'hls/' . $postId;
        $playlistName = 'master.m3u8';
        $files = glob($localHlsDir . DIRECTORY_SEPARATOR . '*') ?: [];
        if (!$files) {
            return null;
        }

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            $name = basename($file);
            self::publishHlsFile($file, $folder . '/' . $name, $name);
        }

        return $folder . '/' . $playlistName;
    }

    private static function publishHlsFile(string $localPath, string $relativeKey, string $name): void
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $mime = $ext === 'm3u8' ? 'application/vnd.apple.mpegurl' : ($ext === 'ts' ? 'video/mp2t' : 'application/octet-stream');
        $folder = dirname($relativeKey);
        $fileName = basename($relativeKey);

        if (DreamlandStorageMode::useLocalDisk()) {
            $root = DreamlandMediaUrl::localUploadDirs()[0] ?? (Yii::getAlias('@runtime/uploads/image'));
            $destDir = dirname($root) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $folder);
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0775, true);
            }
            @copy($localPath, $destDir . DIRECTORY_SEPARATOR . $fileName);
            return;
        }

        if (DreamlandWasabiStorage::isConfigured()) {
            DreamlandWasabiStorage::putLocalFile($folder, $fileName, $localPath, $mime);
        }
    }

    /** @param list<string> $args */
    private static function runFfmpeg(string $ffmpeg, array $args): void
    {
        $escaped = array_map(static function ($part) {
            return escapeshellarg((string) $part);
        }, array_merge([$ffmpeg], $args));
        $cmd = implode(' ', $escaped);
        $out = [];
        $code = 0;
        exec($cmd . ' 2>&1', $out, $code);
        if ($code !== 0) {
            throw new \RuntimeException('ffmpeg failed: ' . implode("\n", $out));
        }
    }

    /** @return array{0:int,1:int} */
    private static function probeDimensions(string $path): array
    {
        $ffprobe = str_replace('ffmpeg', 'ffprobe', basename(self::resolveFfmpegBinary() ?? 'ffmpeg'));
        $bin = dirname(self::resolveFfmpegBinary() ?? '/usr/bin/ffmpeg') . '/' . $ffprobe;
        if (!is_executable($bin)) {
            $bin = 'ffprobe';
        }
        $cmd = escapeshellarg($bin) . ' -v error -select_streams v:0 -show_entries stream=width,height -of csv=p=0 '
            . escapeshellarg($path);
        $out = [];
        @exec($cmd, $out, $code);
        if ($code === 0 && !empty($out[0])) {
            $parts = array_map('intval', explode(',', $out[0]));
            return [$parts[0] ?? 0, $parts[1] ?? 0];
        }
        return [0, 0];
    }

    private static function guessMime(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $map = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'm3u8' => 'application/vnd.apple.mpegurl',
            'ts' => 'video/mp2t',
        ];
        return $map[$ext] ?? 'application/octet-stream';
    }

    private static function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                self::removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
