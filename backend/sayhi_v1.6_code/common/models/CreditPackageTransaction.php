<?php

namespace common\models;

use yii\db\ActiveRecord;

class CreditPackageTransaction extends ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    public static function tableName()
    {
        return 'credit_package_transactions';
    }
}
