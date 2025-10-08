<?php

use common\helpers\ArrayHelper;
use common\models\room\Level;
$level = ArrayHelper::kSort(Level::getLevelDropdown());

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>发红包</title>
    <link rel="stylesheet" href="/resources/plugins/layui/css/layui.css">
</head>
<body>
<div style="padding: 20px">
    <p>普通红包</p>
    <form class="layui-form layui-form-pane" action="" method="post">
        <div class="layui-form-item">
            <div class="layui-inline">
                <label class="layui-form-label">金额</label>
                <div class="layui-input-block">
                    <input type="text" name="amount" lay-verify="required|number" autocomplete="off" class="layui-input">
                </div>
            </div>
            <div class="layui-inline">
                <label class="layui-form-label">个数</label>
                <div class="layui-input-inline">
                    <input type="text" name="count" lay-verify="required|number" autocomplete="off" class="layui-input">
                </div>
            </div>
            <div class="layui-inline">
                <label class="layui-form-label">所需积分</label>
                <div class="layui-input-inline">
                    <input type="text" name="integral" autocomplete="off" class="layui-input" value="0">
                </div>
            </div>
            <div class="layui-inline">
                <label class="layui-form-label">所需等级</label>
                <div class="layui-input-inline">
                    <select name="level_id" lay-verify="required" lay-search="">
                        <?php foreach ($level as $k => $v):?>
                        <option value="<?= $k?>"><?= $v?></option>
                        <?php endforeach;?>
                    </select>
                    <div class="layui-form-select">
                        <div class="layui-select-title">
                            <input type="text" placeholder="选择等级" value="" class="layui-input"><i class="layui-edge"></i>
                        </div>
                        <dl class="layui-anim layui-anim-upbit" style="">
                            <?php foreach ($level as $k => $v):?>
                                <dd lay-value="<?= $k?>" class=""><?= $v?></dd>
                            <?php endforeach;?>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="layui-form-item">
            <div style="text-align: center">
                <button class="layui-btn layui-btn-danger" lay-submit="" lay-filter="*" id="send">发送红包</button>
            </div>
        </div>
    </form>
</div>
</body>
<script src="/resources/plugins/layui/layui.js"></script>
<script>
    layui.use(['form', 'jquery', 'layer'], function () {
        var form = layui.form, $ = layui.jquery, layer = layui.layer;
        form.on('submit(*)', function(data){
            data.field['room_id'] = getQueryVariable('room_id');
            $.post('redpackage', data.field, function (res) {
                if (res.code === '200') {
                    parent.layer.msg(res.message, {zIndex: parent.layer.zIndex});
                    var index = parent.layer.getFrameIndex(window.name);
                    parent.layer.close(index);
                } else {
                    layer.msg(res.message);
                }
            });
            return false; //阻止表单跳转。如果需要表单跳转，去掉这段即可。
        });
        function getQueryVariable(variable)
        {
            var query = window.location.search.substring(1);
            var vars = query.split("&");
            for (var i=0;i<vars.length;i++) {
                var pair = vars[i].split("=");
                if(pair[0] == variable){return pair[1];}
            }
            return(false);
        }
    });
</script>
</html>