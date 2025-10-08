<?php

?>

<div class="modal-content">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span>
        </button>
        <h4 class="modal-title">代理关系</h4>
    </div>
    <div class="modal-body">
        <div class="col-sm-5">
            上级(<?= count($parents) - 1 ?>人)
            <?= \backend\widgets\jstree\JsTree::widget([
                'name' => "parents",
                'defaultData' => $parents,
                'selectIds' => $parentsIds,
                'autoOpen' => 'false',
                'theme' => 'relations'
            ]) ?>
        </div>
        <div class="col-sm-5">
            下级(<?= $children_number ?>人)
            <?= \backend\widgets\jstree\JsTree::widget([
                'name' => "children",
                'defaultData' => $children,
                'selectIds' => $childrenIds,
                'autoOpen' => 'false',
                'url' => 'get-children',
                'cid' => $cid,
                'theme' => 'relations'
            ]) ?>
        </div>
    </div>
    <div class="modal-footer">
        备注: <input type="text" style="width: 50%" id="remark" placeholder="(在此填写的备注会追加到会员原有的备注后面)">
        <style>
            #remark::-webkit-input-placeholder {
                color: red;
                font-size: 13px;
            }
        </style>
        <button type="button" class="btn  btn-success" onclick="return remove()">一键解封</button>
        <button type="button" class="btn  btn-danger" onclick="return block()">一键封停</button>
        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
    </div>
</div>
<script>
    function block() {
        if (confirm("确定要封停吗?")) {
            var childrenIds = getCheckTreeIds("children");
            var parentsIds = getCheckTreeIds("parents");
            var remark = document.getElementById("remark").value;
            $.ajax({
                type: "POST",
                url: "<?=\common\helpers\Url::to(['/member/member/block'])?>",
                data: {
                    childrenIds: childrenIds,
                    parentsIds: parentsIds,
                    status: 0,
                    remark: remark,
                },
                dataType: "json",
                success: function (data) {
                    if (parseInt(data.code) === 200) {
                        window.location = "<?= \common\helpers\Url::to(['index'])?>";
                    } else {
                        rfError(data.message);
                    }
                }
            });
        }
    }

    function remove() {
        if (confirm("确定要解封吗?")) {
            var childrenIds = getCheckTreeIds("children");
            var parentsIds = getCheckTreeIds("parents");
            var remark = document.getElementById("remark").value;
            $.ajax({
                type: "POST",
                url: "<?=\common\helpers\Url::to(['/member/member/block'])?>",
                data: {
                    childrenIds: childrenIds,
                    parentsIds: parentsIds,
                    status: 1,
                    remark: remark,
                },
                dataType: "json",
                success: function (data) {
                    if (parseInt(data.code) === 200) {
                        window.location = "<?= \common\helpers\Url::to(['index'])?>";
                    } else {
                        rfError(data.message);
                    }
                }
            });
        }
    }
</script>