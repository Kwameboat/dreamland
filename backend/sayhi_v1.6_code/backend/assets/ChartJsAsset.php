<?php

namespace backend\assets;

use yii\web\AssetBundle;

class ChartJsAsset extends AssetBundle
{
    public $sourcePath = '@vendor/almasaeed2010/adminlte/bower_components/chart.js';
    public $js = [
        'Chart.js',
    ];
}
