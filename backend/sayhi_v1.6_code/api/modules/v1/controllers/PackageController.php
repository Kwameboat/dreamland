<?php

namespace api\modules\v1\controllers;

use common\models\CreditPackage;
use yii\rest\ActiveController;

/**
 * Legacy package endpoint — now returns admin-controlled Dreamland credit packages only.
 */
class PackageController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\package';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['update'], $actions['index'], $actions['delete'], $actions['view']);
        return $actions;
    }

    public function actionIndex()
    {
        $packages = CreditPackage::getActivePackages();
        $legacyShape = array_map(static function (CreditPackage $pkg) {
            return [
                'id' => $pkg->id,
                'name' => $pkg->credit_amount . ' Credits',
                'price' => (float) $pkg->fiat_cost,
                'coin' => (int) $pkg->credit_amount,
                'currency' => $pkg->currency,
                'in_app_purchase_id_ios' => $pkg->id,
                'in_app_purchase_id_android' => $pkg->id,
            ];
        }, $packages);

        return [
            'message' => 'ok',
            'package' => $legacyShape,
        ];
    }
}
