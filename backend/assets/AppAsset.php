<?php

namespace backend\assets;

use yii\web\AssetBundle;
use yii\web\YiiAsset;
use common\widgets\adminlet\AdminLetAsset;

/**
 * Class AppAsset
 * @package backend\assets
 * @author 原创脉冲
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web/resources';

    public $css = [
        'plugins/ycmc/ycmc.css', //原创脉冲定义样式
        'plugins/toastr/toastr.min.css', // 状态通知
        'plugins/fancybox/jquery.fancybox.min.css', // 图片查看
        'plugins/cropper/cropper.min.css',
        'css/rageframe.css',
        'css/rageframe.widgets.css',
        'plugins/datatables/datatables.min.css',
    ];

    public $js = [
        'plugins/ycmc/ycmc.js', // 原创脉冲定义JS
        'plugins/layer/layer.js',
        'plugins/sweetalert/sweetalert.min.js',
        'plugins/fancybox/jquery.fancybox.min.js',
        'js/template.js',
        'js/rageframe.js',
        'js/rageframe.widgets.js',
        'plugins/datatables/datatables.min.js',
    ];

    public $depends = [
        YiiAsset::class,
        AdminLetAsset::class,
        HeadJsAsset::class
    ];

    //定义按需加载JS方法，注意加载顺序在最后
    public static function addScript($view, $jsfile) {
        $view->registerJsFile($jsfile, [AppAsset::class, 'depends' => 'backend\assets\AppAsset']);
    }

    //定义按需加载css方法，注意加载顺序在最后
    public static function addCss($view, $cssfile) {
        $view->registerCssFile($cssfile, [AppAsset::class, 'depends' => 'backend\assets\AppAsset']);
    }
}
