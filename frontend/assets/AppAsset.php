<?php

namespace frontend\assets;

use common\components\View;
use yii\web\AssetBundle;

/**
 * Main frontend application asset bundle.
 */
class AppAsset extends AssetBundle
{
    public $css = [
        'css/main.css',
    ];
    public $js = [
        'js/base.js',
        'js/socket.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'frontend\assets\BootstrapAsset',
        'frontend\assets\plugins\MetisMenuAsset',
    ];
    public $jsOptions = [
        'position' => View::POS_BEGIN
    ];
    
    public function init()
    {
        $this->baseUrl = \Yii::$app->view->theme->baseUrl;
        parent::init(); // TODO: Change the autogenerated stub
    }
}
