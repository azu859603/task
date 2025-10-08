<?php

use backend\assets\AppAsset;
use common\models\tea\Room;

AppAsset::addScript($this, '@web/resources/plugins/tagsinput/jquery.tagsinput.js');
AppAsset::addCss($this, '@web/resources/plugins/tagsinput/jquery.tagsinput.css');
AppAsset::register($this);
?>
<?php foreach ($words as $k => $word):?>
<?php $room = Room::findOne($k)?>
<div class="row">
    <div class="col-xs-12">
        <p><?= $room['name']?></p>
        <div class="box">
            <input id="tags_<?= $k?>" type="text" class="tags" value="<?=$word?>" />
        </div>
    </div>
</div>
<?php endforeach;?>
<?php foreach ($words as $k => $v) {
$js = <<<JS
    $('#tags_{$k}').tagsInput({
        width:'auto',
        defaultText:'添加',
        onAddTag:function(v) {
            $.ajax({
                url: "index",
                type: "post",
                data: {word:v, type:'add', room_id:{$k}},
                success: function (result) {
                    
                }
            });
        },
        onRemoveTag:function(v) {
            $.ajax({
                url: "index",
                type: "post",
                data: {word:v, type:'del', room_id:{$k}},
                success: function (result) {
                    
                }
            });
        }
    });
JS;
    $this->registerJs($js);
}
?>