
<footer class="main-footer">
    <div class="pull-right hidden-xs">
        <?= Yii::$app->debris->backendConfig('web_copyright'); ?>
    </div>
    当前版本：<?= Yii::$app->version; ?>
</footer>
<?php
$this->registerJsFile('@web/resources/plugins/ycmc/socket.js');
$this->registerJsFile('@web/resources/plugins/ycmc/web_socket.js');
$this->render('/chat/index');
?>
