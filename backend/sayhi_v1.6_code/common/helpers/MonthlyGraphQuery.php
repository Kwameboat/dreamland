<?php

namespace common\helpers;

use Yii;

class MonthlyGraphQuery
{
    public static function isPgsql(): bool
    {
        return Yii::$app->db->driverName === 'pgsql';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function query(string $sqlMysql, string $sqlPgsql): array
    {
        $sql = self::isPgsql() ? $sqlPgsql : $sqlMysql;
        $rows = Yii::$app->db->createCommand($sql)->queryAll();
        foreach ($rows as &$row) {
            if (isset($row['month'])) {
                $row['month'] = (int) $row['month'];
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * @param array<int|string, string> $months
     * @param array<int, array<string, mixed>> $rows
     * @return array{data: array<int, int|float>, dataCaption: array<int, string>}
     */
    public static function buildSeries(array $months, array $rows, string $valueKey = 'total_ad'): array
    {
        $data = [];
        $dataCaption = [];
        foreach ($months as $key => $label) {
            $foundKey = array_search((int) $key, array_column($rows, 'month'), true);
            $value = 0;
            if (is_int($foundKey) && isset($rows[$foundKey][$valueKey]) && $rows[$foundKey][$valueKey] !== null) {
                $value = round((float) $rows[$foundKey][$valueKey]);
            }
            $data[] = $value;
            $dataCaption[] = $label;
        }

        return ['data' => $data, 'dataCaption' => $dataCaption];
    }

    public static function emptySeries(array $months): array
    {
        return self::buildSeries($months, []);
    }
}
