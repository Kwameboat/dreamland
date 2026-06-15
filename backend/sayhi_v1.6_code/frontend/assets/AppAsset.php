<?php

namespace frontend\assets;

use yii\web\AssetBundle;
use yii;

/**
 * Main frontend application asset bundle.
 */

if(Yii::$app->language=='ar-AE'){
    $type_direction ="rtl";
}else{
    $type_direction ="ltr";
}
defined('TYPE_DIRECTION') or define('TYPE_DIRECTION', $type_direction);
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    

    public $jsOptions = ['position' => \yii\web\View::POS_HEAD];
    public $css = [
       //'css/site.css',
       //'css/bootstrap.css',
       
       'css/bootstrap.min.css',
      // 'css/font-awesome.all-5-3.css', 
     //   'css/font-awesome.min.css',
      //  'css/style.css',
        (TYPE_DIRECTION == 'rtl') ? 'css/style-rtl.css':'css/style.css',
        'css/owlcarousel/owl.carousel.min.css',
        'css/owlcarousel/owl.theme.default.min.css',
        'css/flag.min.css',
        'https://use.fontawesome.com/releases/v5.3.1/css/all.css',
       'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'
        
        
      

        
    ];

    public $js = [
    //  'js/jquery.min.js',
        'js/slick.js',
      
        'js/bootstrap.min.js',
        'js/owlcarousel/owl.carousel.min.js',
        'js/popper.min.js',
        
        'js/custom.js'
        

    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap4\BootstrapAsset',
    ];
}

