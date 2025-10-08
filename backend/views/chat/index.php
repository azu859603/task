<?php
use backend\assets\AppAsset;

AppAsset::addCss($this, '@web/../resources/plugins/layui/css/layui.css');
AppAsset::addCss($this, '@web/../resources/css/contextMenu.css');
AppAsset::addScript($this, '@web/../resources/plugins/layui/layui.js');
AppAsset::addScript($this, '@web/../resources/js/webim.config.js?v=1');
AppAsset::addScript($this, '@web/../resources/js/socket.js');
AppAsset::addScript($this, '@web/../resources/js/redpackage_manager.js');
AppAsset::register($this);
$identity = Yii::$app->user->identity;
$id = $identity->getId();
$js = <<< JS
layui.config({
    base: '../resources/js/'
}).extend({
    socket: 'socket',
});
layui.use(['socket'], function (socket) {
    socket.config({
        myId: {$id},
        myType: 'manager',
    });
    
});
JS;
$this->registerJs($js);
