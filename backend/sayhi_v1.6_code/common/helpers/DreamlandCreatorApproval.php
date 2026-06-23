<?php

namespace common\helpers;

use common\models\DreamlandAudience;
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

    /** @var bool|null */
    private static $columnExistsCache;

    public static function hasCreatorStatusColumn(): bool
    {
        if (self::$columnExistsCache !== null) {
            return self::$columnExistsCache;
        }

        $schema = Yii::$app->db->schema->getTableSchema('user', true);
        $exists = $schema && isset($schema->columns['dreamland_creator_status']);
        if (!$exists) {
            Yii::$app->db->schema->refreshTableSchema('user');
            $schema = Yii::$app->db->schema->getTableSchema('user', true);
            $exists = $schema && isset($schema->columns['dreamland_creator_status']);
        }
        if (!$exists) {
            $exists = DreamlandAudience::hasUserColumn('dreamland_creator_status');
        }

        self::$columnExistsCache = $exists;
        return $exists;
    }

    /** Ensure column exists (creates it on MySQL if missing). */
    public static function ensureCreatorStatusColumn(): bool
    {
        if (self::hasCreatorStatusColumn()) {
            return true;
        }

        try {
            $db = Yii::$app->db;
            if ($db->driverName !== 'mysql') {
                return false;
            }
            $after = DreamlandAudience::hasUserColumn('dreamland_account_type')
                ? ' AFTER `dreamland_account_type`'
                : '';
            $db->createCommand(
                "ALTER TABLE `user` ADD COLUMN `dreamland_creator_status` VARCHAR(16) NOT NULL DEFAULT 'none'{$after}"
            )->execute();
            self::clearSchemaCache();
            return self::hasCreatorStatusColumn();
        } catch (\Throwable $e) {
            Yii::warning($e->getMessage(), __METHOD__);
            return false;
        }
    }

    public static function fetchStatusFromDb(int $userId): ?string
    {
        if ($userId <= 0 || !self::hasCreatorStatusColumn()) {
            return null;
        }

        try {
            $value = Yii::$app->db->createCommand(
                'SELECT [[dreamland_creator_status]] FROM {{%user}} WHERE [[id]] = :id',
                [':id' => $userId]
            )->queryScalar();
            return $value === false ? null : (string) $value;
        } catch (\Throwable $e) {
            Yii::warning($e->getMessage(), __METHOD__);
            return null;
        }
    }

    public static function resolveStatus(?ActiveRecord $user): string
    {
        if (!$user) {
            return self::STATUS_NONE;
        }

        if (!self::hasCreatorStatusColumn()) {
            return self::isCreatorIdentity($user) ? self::STATUS_PENDING : self::STATUS_NONE;
        }

        $userId = (int) $user->getPrimaryKey();
        $status = strtolower(trim((string) ($user->getAttribute('dreamland_creator_status') ?? '')));

        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED], true)
            && $userId > 0) {
            $fromDb = self::fetchStatusFromDb($userId);
            if ($fromDb !== null && $fromDb !== '') {
                $status = strtolower(trim($fromDb));
                $user->setAttribute('dreamland_creator_status', $status);
            }
        }

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

    /**
     * Persist approval status via SQL (reliable even when Yii schema cache is stale).
     *
     * @return bool true when DB row matches requested status
     */
    public static function persistCreatorApproval(ActiveRecord $user, string $status, int $accountStatus = 10): bool
    {
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED], true)) {
            return false;
        }

        if (!self::ensureCreatorStatusColumn()) {
            return false;
        }

        $userId = (int) $user->getPrimaryKey();
        if ($userId <= 0) {
            return false;
        }

        self::applyCreatorIdentity($user);

        $update = [
            'dreamland_creator_status' => $status,
            'status' => $accountStatus,
        ];
        if (DreamlandAudience::hasUserColumn('role')) {
            $update['role'] = 4;
        }
        if (DreamlandAudience::hasUserColumn('dreamland_account_type')) {
            $update['dreamland_account_type'] = 'creator';
        }

        try {
            Yii::$app->db->createCommand()->update('{{%user}}', $update, ['id' => $userId])->execute();
            self::clearSchemaCache();

            foreach ($update as $key => $value) {
                if ($user->hasAttribute($key)) {
                    $user->setAttribute($key, $value);
                }
            }
            $user->setAttribute('dreamland_creator_status', $status);

            return strtolower(trim((string) self::fetchStatusFromDb($userId))) === $status;
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
            return false;
        }
    }

    public static function setCreatorStatus(ActiveRecord $user, string $creatorStatus): void
    {
        if (!self::ensureCreatorStatusColumn()) {
            return;
        }
        self::applyCreatorIdentity($user);
        $user->setAttribute('dreamland_creator_status', $creatorStatus);
        $userId = (int) $user->getPrimaryKey();
        if ($userId > 0) {
            self::persistCreatorApproval($user, $creatorStatus, (int) ($user->getAttribute('status') ?: 10));
        }
    }

    public static function approve(ActiveRecord $user): void
    {
        self::persistCreatorApproval($user, self::STATUS_APPROVED, 10);
    }

    public static function markPending(ActiveRecord $user): void
    {
        self::persistCreatorApproval($user, self::STATUS_PENDING, (int) ($user->getAttribute('status') ?: 10));
    }

    public static function reject(ActiveRecord $user): void
    {
        self::persistCreatorApproval($user, self::STATUS_REJECTED, 10);
    }

    public static function demoteToViewer(ActiveRecord $user): void
    {
        if ($user->hasAttribute('role')) {
            $user->setAttribute('role', 3);
        }
        if ($user->hasAttribute('dreamland_account_type')) {
            $user->setAttribute('dreamland_account_type', 'viewer');
        }

        $userId = (int) $user->getPrimaryKey();
        if (self::hasCreatorStatusColumn() && $userId > 0) {
            Yii::$app->db->createCommand()->update(
                '{{%user}}',
                ['dreamland_creator_status' => self::STATUS_NONE],
                ['id' => $userId]
            )->execute();
            self::clearSchemaCache();
        }
        if ($user->hasAttribute('dreamland_creator_status')) {
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
        $status = self::resolveStatus($user);
        return in_array($status, [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED], true);
    }

    /** Align role + account_type + creator_status for PWA signups with partial data. */
    public static function syncCreatorRecord(ActiveRecord $user): void
    {
        $status = self::resolveStatus($user);
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED], true)) {
            self::persistCreatorApproval($user, self::STATUS_PENDING, (int) ($user->getAttribute('status') ?: 10));
            return;
        }
        self::applyCreatorIdentity($user);
        $userId = (int) $user->getPrimaryKey();
        if ($userId > 0) {
            self::persistCreatorApproval($user, $status, (int) ($user->getAttribute('status') ?: 10));
        }
    }

    private static function clearSchemaCache(): void
    {
        self::$columnExistsCache = null;
        Yii::$app->db->schema->refreshTableSchema('user');
    }
}
