<?php

namespace common\models;

use app\models\User;
use common\helpers\DreamlandCreatorApproval;
use Yii;
use yii\db\ActiveQuery;

/**
 * Dreamland audience groups for targeted notifications and admin filters.
 */
class DreamlandAudience
{
    public const VIEWERS = 'viewers';
    public const CREATORS = 'creators';
    public const ADMINS = 'admins';
    public const CUSTOM = 'custom';

    public static function labels(): array
    {
        return [
            self::VIEWERS => 'General users (viewers)',
            self::CREATORS => 'Content creators',
            self::ADMINS => 'Administrators',
            self::CUSTOM => 'Custom selection',
        ];
    }

    public static function label(string $audience): string
    {
        return self::labels()[$audience] ?? ucfirst($audience);
    }

    public static function isValid(string $audience): bool
    {
        return isset(self::labels()[$audience]);
    }

    public static function userSchema()
    {
        static $schema = false;
        if ($schema === false) {
            $schema = Yii::$app->db->schema->getTableSchema(User::tableName(), true);
        }
        return $schema;
    }

    public static function hasUserColumn(string $column): bool
    {
        $schema = self::userSchema();
        if ($schema && isset($schema->columns[$column])) {
            return true;
        }

        if (self::columnExistsInDatabase(User::tableName(), $column)) {
            Yii::$app->db->schema->refreshTableSchema(User::tableName());
            return true;
        }

        return false;
    }

    private static function columnExistsInDatabase(string $table, string $column): bool
    {
        try {
            $db = Yii::$app->db;
            if ($db->driverName !== 'mysql') {
                return false;
            }

            return (int) $db->createCommand(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c',
                [':t' => $table, ':c' => $column]
            )->queryScalar() > 0;
        } catch (\Throwable $e) {
            Yii::warning($e->getMessage(), __METHOD__);
            return false;
        }
    }

    public static function viewerQuery(): ActiveQuery
    {
        $query = User::find()
            ->where(['role' => User::ROLE_CUSTOMER])
            ->andWhere(['<>', 'status', User::STATUS_DELETED]);

        if (self::hasUserColumn('dreamland_account_type')) {
            $query->andWhere([
                'or',
                ['dreamland_account_type' => 'viewer'],
                ['dreamland_account_type' => null],
                ['dreamland_account_type' => ''],
            ]);
        }

        return $query;
    }

    public static function creatorQuery(): ActiveQuery
    {
        $or = [
            ['role' => User::ROLE_AGENT],
        ];

        if (self::hasUserColumn('dreamland_account_type')) {
            $or[] = ['dreamland_account_type' => 'creator'];
        }
        if (self::hasUserColumn('dreamland_creator_status')) {
            $or[] = [
                'dreamland_creator_status' => [
                    DreamlandCreatorApproval::STATUS_PENDING,
                    DreamlandCreatorApproval::STATUS_APPROVED,
                    DreamlandCreatorApproval::STATUS_REJECTED,
                ],
            ];
        }

        return User::find()
            ->where(['<>', 'status', User::STATUS_DELETED])
            ->andWhere(array_merge(['or'], $or));
    }

    public static function adminQuery(): ActiveQuery
    {
        return User::find()
            ->where(['role' => [User::ROLE_ADMIN, User::ROLE_SUBADMIN]])
            ->andWhere(['<>', 'status', User::STATUS_DELETED]);
    }

    /** @return int[] */
    public static function resolveUserIds(string $audience, array $customIds = []): array
    {
        if ($audience === self::CUSTOM) {
            return array_values(array_unique(array_filter(array_map('intval', $customIds))));
        }

        switch ($audience) {
            case self::VIEWERS:
                $query = self::viewerQuery();
                break;
            case self::CREATORS:
                $query = self::creatorQuery();
                break;
            case self::ADMINS:
                $query = self::adminQuery();
                break;
            default:
                return [];
        }

        return array_map('intval', $query->select('id')->column());
    }

    public static function applyToQuery(ActiveQuery $query, string $audience): ActiveQuery
    {
        switch ($audience) {
            case self::CREATORS:
                $parts = [['user.role' => User::ROLE_AGENT]];
                if (self::hasUserColumn('dreamland_account_type')) {
                    $parts[] = ['user.dreamland_account_type' => 'creator'];
                }
                return $query->andWhere(array_merge(['or'], $parts));
            case self::ADMINS:
                return $query->andWhere(['user.role' => [User::ROLE_ADMIN, User::ROLE_SUBADMIN]]);
            case self::VIEWERS:
                $q = $query->andWhere(['user.role' => User::ROLE_CUSTOMER]);
                if (self::hasUserColumn('dreamland_account_type')) {
                    $q->andWhere([
                        'or',
                        ['user.dreamland_account_type' => 'viewer'],
                        ['user.dreamland_account_type' => null],
                        ['user.dreamland_account_type' => ''],
                    ]);
                }
                return $q;
            default:
                return $query;
        }
    }
}
