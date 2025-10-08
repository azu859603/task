<div id="<?= $name; ?>"></div>
<style>
    .jstree-default-contextmenu {
        z-index: 9999;
    }
</style>
<?php

$this->registerJs(<<<JS
    var treeId = $name;
    var treeCheckIds = JSON.parse('$selectIds');
    var treeData = JSON.parse('$defaultData');
    
    relationsTree(treeData, $(treeId).attr('id'), treeCheckIds, {$autoOpen}, '{$url}', '{$cid}');
JS
);
?>