<?php

namespace common\helpers;

/**
 * Active upload backend: local cPanel disk (trials) vs Wasabi (production).
 */
class DreamlandStorageMode
{
    public static function useLocalDisk(): bool
    {
        if (getenv('DREAMLAND_FORCE_LOCAL_UPLOADS') === '1') {
            return true;
        }
        if (getenv('DREAMLAND_TRIAL_MODE') === '1') {
            return true;
        }

        $mode = strtolower(trim((string) (getenv('DREAMLAND_STORAGE') ?: '')));
        if ($mode === 'local') {
            return true;
        }
        if ($mode === 'wasabi') {
            return false;
        }

        return false;
    }

    public static function activeLabel(): string
    {
        if (self::useLocalDisk()) {
            return 'local';
        }

        return DreamlandWasabiStorage::isConfigured() ? 'wasabi' : 'local';
    }
}
