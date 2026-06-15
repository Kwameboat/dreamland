<?php
///namespace dmstr\web;
namespace backend\assets;
//namespace dmstr\adminlte\web;
use yii\base\Exception;
use yii\web\AssetBundle as BaseAdminLteAsset;




/**
 * AdminLte AssetBundle
 * @since 0.1
 */
class AdminLteAsset extends BaseAdminLteAsset
{
    public $sourcePath = '@vendor/almasaeed2010/adminlte/dist';
    public $jsOptions = ['position' => \yii\web\View::POS_HEAD];

    public $css = [
        'css/AdminLTE.css',
        
    ];
    public $js = [
        'js/adminlte.min.js',
        'chart.js/Chart.js'
        
    ];
    public $depends = [
        'rmrevin\yii\fontawesome\AssetBundle',
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
        'yii\bootstrap\BootstrapPluginAsset',
    ];

    
    

    
    /**
     * @var string|bool Choose skin color, eg. `'skin-blue'` or set `false` to disable skin loading
     * @see https://almsaeedstudio.com/themes/AdminLTE/documentation/index.html#layout
     */
    public $skin = '_all-skins';
    //public $skin = 'skin-purple';

    /**
     * @inheritdoc
     */
    public function init()
    {
        
        // Append skin color file if specified
        if ($this->skin) {
            /*if (('_all-skins' !== $this->skin) && (strpos($this->skin, 'skin-') !== 0)) {
                throw new Exception('Invalid skin specified');
            }*/

            $this->css[] = sprintf('css/skins/%s.min.css', $this->skin);
            //print_r($this->css);
            
        }
        

        parent::init();
    }
    
}
