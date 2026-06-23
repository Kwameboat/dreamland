<?php

namespace common\helpers;

use Yii;
use yii\db\ActiveRecord;

/**
 * Keeps admin creator approval in sync with PWA upload gates (dreamland_creator_status).
 */
class DreamlandCreatorApproval
{
    public const STATUS_NONE = 'none';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public static function hasCreatorStatusColumn(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $schema = Yii::$app->db->schema->getTableSchema('user', true);
        $cached = $schema && isset($schema->columns['dreamland_creator_status']);
        return $cached;
    }

    public static function resolveStatus(?ActiveRecord $user): string
    {
        if (!$user) {
            return self::STATUS_NONE;
        }

        if (!self::hasCreatorStatusColumn()) {
            if (self::isCreatorIdentity($user)) {
                return self::STATUS_PENDING;
            }
            return self::STATUS_NONE;
        }

        $status = strtolower(trim((string) ($user->getAttribute('dreamland_creator_status') ?? self::STATUS_NONE)));
        if (in_array($status, [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED], true)) {
            return $status;
        }
        if (self::isCreatorIdentity($user)) {
            return self::STATUS_PENDING;
        }
        return self::STATUS_NONE;
    }

    private static function isCreatorIdentity(?ActiveRecord $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->hasAttribute('dreamland_account_type')
            && (string) $user->getAttribute('dreamland_account_type') === 'creator') {
            return true;
        }
        return $user->hasAttribute('role') && (int) $user->getAttribute('role') === 4;
    }

    public static function isPending(?ActiveRecord $user): bool
    {
        return self::resolveStatus($user) === self::STATUS_PENDING;
    }

    public static function isApproved(?ActiveRecord $user): bool
    {
        return self::resolveStatus($user) === self::STATUS_APPROVED;
    }

    public static function applyCreatorIdentity(ActiveRecord $user): void
    {
        if ($user->hasAttribute('role')) {
            $user->setAttribute('role', 4);
        }
        if ($user->hasAttribute('dreamland_account_type')) {
            $user->setAttribute('dreamland_account_type', 'creator');
        }
    }

    public static function setCreatorStatus(ActiveRecord $user, string $creatorStatus): void
    {
        if (!self::hasCreatorStatusColumn()) {
            return;
        }
        self::applyCreatorIdentity($user);
        $user->setAttribute('dreamland_creator_status', $creatorStatus);
    }

    public static function approve(ActiveRecord $user): void
    {
        self::setCreatorStatus($user, self::STATUS_APPROVED);
    }

    public static function markPending(ActiveRecord $user): void
    {
        self::setCreatorStatus($user, self::STATUS_PENDING);
    }

    public static function reject(ActiveRecord $user): void
    {
        self::setCreatorStatus($user, self::STATUS_REJECTED);
    }

    public static function demoteToViewer(ActiveRecord $user): void
    {
        if ($user->hasAttribute('role')) {
            $user->setAttribute('role', 3);
        }
        if ($user->hasAttribute('dreamland_account_type')) {
            $user->setAttribute('dreamland_account_type', 'viewer');
        }
        if (self::hasCreatorStatusColumn()) {
            $user->setAttribute('dreamland_creator_status', self::STATUS_NONE);
        }
    }

    public static function label(string $status): string
    {
        switch ($status) {
            case self::STATUS_PENDING:
                return 'Pending approval';
            case self::STATUS_APPROVED:
                return 'Approved';
            case self::STATUS_REJECTED:
                return 'Rejected';
            default:
                return 'Not set';
        }
    }

    /** True when DB row is (or should be) managed as a content creator in admin. */
    public static function looksLikeCreator(?ActiveRecord $user): bool
    {
        if (!$user) {
            return false;
        }
        if (self::isCreatorIdentity($user)) {
            return true;
        }
        if (!self::hasCreatorStatusColumn()) {
            return false;
        }
        $status = strtolower(trim((string) ($user->getAttribute('dreamland_creator_status') ?? '')));
        return in_array($status, [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED], true);
    }

    /** Align role + account_type + creator_status for PWA signups with partial data. */
    public static function syncCreatorRecord(ActiveRecord $user): void
    {
        self::applyCreatorIdentity($user);
        if (self::hasCreatorStatusColumn()) {
            $status = strtolower(trim((string) ($user->getAttribute('dreamland_creator_status') ?? '')));
            if (!in_array($status, [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED], true)) {
                $user->setAttribute('dreamland_creator_status', self::STATUS_PENDING);
            }
        }
        $user->save(false);
    }
}
