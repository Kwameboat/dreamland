<?php

namespace common\helpers;

use common\models\DreamlandSetting;
use common\models\Setting;
use yii\web\UploadedFile;

/**
 * Central upload / live duration limits — Dreamland admin first, legacy setting table fallback.
 */
class DreamlandUploadLimits
{
    public const DEFAULT_REEL_DURATION = 60;
    public const DEFAULT_REEL_UPLOAD_MB = 128;
    public const DEFAULT_LIVE_DURATION = 3600;

    /** @return array{max_reel_duration_seconds:int,max_reel_upload_mb:int,max_live_duration_seconds:int} */
    public static function getLimits(): array
    {
        $limits = [
            'max_reel_duration_seconds' => self::DEFAULT_REEL_DURATION,
            'max_reel_upload_mb' => self::DEFAULT_REEL_UPLOAD_MB,
            'max_live_duration_seconds' => self::DEFAULT_LIVE_DURATION,
        ];

        try {
            $dreamland = DreamlandSetting::getSettings();
            if ($dreamland->hasAttribute('max_reel_duration_seconds')) {
                $v = (int) $dreamland->getAttribute('max_reel_duration_seconds');
                if ($v > 0) {
                    $limits['max_reel_duration_seconds'] = min(600, $v);
                }
            }
            if ($dreamland->hasAttribute('max_reel_upload_mb')) {
                $v = (int) $dreamland->getAttribute('max_reel_upload_mb');
                if ($v > 0) {
                    $limits['max_reel_upload_mb'] = min(512, $v);
                }
            }
            if ($dreamland->hasAttribute('max_live_duration_seconds')) {
                $v = (int) $dreamland->getAttribute('max_live_duration_seconds');
                if ($v > 0) {
                    $limits['max_live_duration_seconds'] = min(86400, $v);
                }
            }
        } catch (\Throwable $e) {
            // dreamland_settings may not exist yet on older DBs
        }

        try {
            $legacy = (new Setting())->getSettingData();
            if (!empty($legacy->maximum_video_duration_allowed)) {
                $v = (int) $legacy->maximum_video_duration_allowed;
                if ($v > 0 && $limits['max_reel_duration_seconds'] === self::DEFAULT_REEL_DURATION) {
                    $limits['max_reel_duration_seconds'] = min(600, $v);
                }
            }
            if (!empty($legacy->upload_max_file)) {
                $v = (int) $legacy->upload_max_file;
                if ($v > 0 && $limits['max_reel_upload_mb'] === self::DEFAULT_REEL_UPLOAD_MB) {
                    $limits['max_reel_upload_mb'] = min(512, max(1, $v));
                }
            }
            if (!empty($legacy->free_live_tv_duration_to_view)) {
                $v = (int) $legacy->free_live_tv_duration_to_view;
                if ($v > 0 && $limits['max_live_duration_seconds'] === self::DEFAULT_LIVE_DURATION) {
                    $limits['max_live_duration_seconds'] = min(86400, $v);
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return $limits;
    }

    public static function maxReelUploadBytes(): int
    {
        $limits = self::getLimits();
        return $limits['max_reel_upload_mb'] * 1024 * 1024;
    }

    /** @return array{statusCode:int,message:string}|null */
    public static function validateVideoFile(UploadedFile $file): ?array
    {
        $limits = self::getLimits();
        $maxBytes = $limits['max_reel_upload_mb'] * 1024 * 1024;
        if ($file->size > $maxBytes) {
            return [
                'statusCode' => 422,
                'message' => 'Video exceeds ' . $limits['max_reel_upload_mb'] . ' MB upload limit.',
            ];
        }

        $duration = self::probeVideoDuration($file->tempName);
        if ($duration !== null && $duration > $limits['max_reel_duration_seconds']) {
            return [
                'statusCode' => 422,
                'message' => 'Video is ' . (int) ceil($duration) . 's — max allowed is '
                    . $limits['max_reel_duration_seconds'] . 's.',
            ];
        }

        return null;
    }

    public static function probeVideoDuration(string $path): ?float
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }
        try {
            if (!class_exists(\getID3::class)) {
                return null;
            }
            $analyzer = new \getID3();
            $info = $analyzer->analyze($path);
            if (!empty($info['playtime_seconds'])) {
                return (float) $info['playtime_seconds'];
            }
        } catch (\Throwable $e) {
            return null;
        }
        return null;
    }

    /** @return array<string,int> */
    public static function forApi(): array
    {
        $limits = self::getLimits();
        return [
            'max_reel_duration_seconds' => $limits['max_reel_duration_seconds'],
            'max_reel_upload_mb' => $limits['max_reel_upload_mb'],
            'max_reel_upload_bytes' => $limits['max_reel_upload_mb'] * 1024 * 1024,
            'max_live_duration_seconds' => $limits['max_live_duration_seconds'],
        ];
    }
}
