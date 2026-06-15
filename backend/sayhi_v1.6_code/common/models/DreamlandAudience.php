<?php

namespace common\models;

use app\models\User;
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

    public static function viewerQuery(): ActiveQuery
    {
        return User::find()
            ->where(['role' => User::ROLE_CUSTOMER])
            ->andWhere(['<>', 'status', User::STATUS_DELETED])
            ->andWhere([
                'or',
                ['dreamland_account_type' => 'viewer'],
                ['dreamland_account_type' => null],
                ['dreamland_account_type' => ''],
            ]);
    }

    public static function creatorQuery(): ActiveQuery
    {
        return User::find()
            ->where(['<>', 'status', User::STATUS_DELETED])
            ->andWhere([
                'or',
                ['dreamland_account_type' => 'creator'],
                ['role' => User::ROLE_AGENT],
            ]);
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

        $query = match ($audience) {
            self::VIEWERS => self::viewerQuery(),
            self::CREATORS => self::creatorQuery(),
            self::ADMINS => self::adminQuery(),
            default => null,
        };

        if (!$query) {
            return [];
        }

        return array_map('intval', $query->select('id')->column());
    }

    public static function applyToQuery(ActiveQuery $query, string $audience): ActiveQuery
    {
        return match ($audience) {
            self::CREATORS => $query->andWhere([
                'or',
                ['user.dreamland_account_type' => 'creator'],
                ['user.role' => User::ROLE_AGENT],
            ]),
            self::ADMINS => $query->andWhere(['user.role' => [User::ROLE_ADMIN, User::ROLE_SUBADMIN]]),
            self::VIEWERS => $query->andWhere(['user.role' => User::ROLE_CUSTOMER])->andWhere([
                'or',
                ['user.dreamland_account_type' => 'viewer'],
                ['user.dreamland_account_type' => null],
                ['user.dreamland_account_type' => ''],
            ]),
            default => $query,
        };
    }
}
