<?php

namespace common\helpers;

use Yii;

class SqlDialect
{
    public static function isPgsql(): bool
    {
        return Yii::$app->db->driverName === 'pgsql';
    }

    /**
     * Engagement + recency ranking for feed ORDER BY.
     */
    public static function feedRankingExpression(
        float $viewWeight,
        float $likeWeight,
        float $recencyWeight,
        ?int $categoryId = null,
        ?float $genreWeight = null,
        ?float $shareWeight = null
    ): string {
        $isPg = self::isPgsql();
        $log = $isPg ? 'ln' : 'LOG';
        $recency = $isPg
            ? '(EXTRACT(EPOCH FROM NOW())::bigint - post.created_at)'
            : '(UNIX_TIMESTAMP() - post.created_at)';

        $expr = '('
            . $log . '(1 + post.total_view) * ' . $viewWeight
            . ' + ' . $log . '(1 + post.total_like) * ' . $likeWeight;

        if ($shareWeight !== null) {
            $expr .= ' + ' . $log . '(1 + post.total_share) * ' . $shareWeight;
        }

        $expr .= ' + GREATEST(0, 1 - ' . $recency . ' / 604800.0) * ' . $recencyWeight;

        if ($categoryId > 0) {
            $bonus = $genreWeight ?? 0.35;
            $expr .= $isPg
                ? ' + CASE WHEN post.category_id = ' . (int) $categoryId . ' THEN ' . $bonus . ' ELSE 0 END'
                : ' + IF(post.category_id = ' . (int) $categoryId . ', ' . $bonus . ', 0)';
        }

        return $expr . ') DESC';
    }
}
