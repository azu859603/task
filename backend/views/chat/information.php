<?php

use common\enums\GenderEnum;
use common\helpers\ImageHelper;
use common\helpers\DateHelper;
use common\models\base\User;

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>好友/群资料</title>
    <link rel="stylesheet" href="/resources/plugins/layui/css/layui.css">
    <style type="text/css">
        .layim-msgbox {
            margin: 15px;
        }
        .layim-msgbox li {
            position: relative;
            margin-bottom: 10px;
            padding: 0 0 10px 170px;
            line-height: 22px;
            border-bottom: 1px dotted #e2e2e2;
            width: 200px;
            text-align: left;
        }
        .layim-msgbox {
            margin: 0;
            padding: 10px 0;
            border: none;
            text-align: center;
            color: #999;
        }
        .layim-msgbox {
            padding: 0 10px 10px 10px;
        }
        .layim-msgbox li p span {
            padding-left: 5px;
            color: #999;
        }
        .layim-msgbox li p em {
            font-style: normal;
            color: #FF5722;
        }
        .layim-msgbox-avatar {
            position: absolute;
            left: 40px;
            top: 20px;
            width: 50px;
            height: 50px;
        }
        .layim-msgbox-user {
            padding-top: 5px;
        }
        .layim-msgbox {
            padding: 0 15px;
            margin-left: 5px;
        }

        .pt15 {
            padding-top: 15px;
        }
        .label {
            float: left;
            display: block;
            padding: 9px 5px 9px 20px;
            width: 85px;
            font-weight: 400;
            text-align: right;
        }
        .label_key {
            float: left;
            display: block;
            padding: 9px 5px;
            font-weight: 400;
            word-break:break-all;
            word-wrap:break-word;
        }
    </style>
</head>
<body>
<div class="layui-form">
    <div class="layui-form">
        <div class="layui-form-item pt15">
            <div class="layim-msgbox">
                <li>
                    <img src="<?= ImageHelper::defaultHeaderPortrait($model['head_portrait'])?>" class="layui-circle layim-msgbox-avatar">
                    <p class="layim-msgbox-user"><span>用户名&nbsp;</span> <?= $model['username']?> </p>
                    <p class="layim-msgbox-user"><span style="letter-spacing: 5px;">昵 称</span> <?= $model['nickname']?> </p>
                    <p class="layim-msgbox-user"><span style="letter-spacing: 5px;">备 注</span> <?= $model['notice']?> </p>
                </li>
            </div>
        </div>
        <div class="layui-col-xs12">
            <label class="label">在线状态</label>
            <label class="label_key"> <?= User::$onlineExplain[$model['online_status']]?> </label>
        </div>
        <div class="layui-col-xs12">
            <label class="label">钱包</label>
            <label class="label_key"> <?= $model->account->user_money?> </label>
        </div>
        <div class="layui-col-xs12">
            <label class="label">积分</label>
            <label class="label_key"> <?= $model->account->user_integral?> </label>
        </div>
        <div class="layui-col-xs12">
            <label class="label">等级</label>
            <label class="label_key"> <?= $model->level ? $model->level->name : '无'?> </label>
        </div>
        <div class="layui-col-xs12"><label class="label">性&nbsp;&nbsp;别</label>
            <label class="label_key"> <?= GenderEnum::$listExplain[$model['gender']]?> </label>
        </div>
        <div class="layui-col-xs12"><label class="label">注册时间</label>
            <div class="label_key"><?= DateHelper::dateTime($model['created_at'])?></div>
        </div>
        <div class="layui-col-xs12"><label class="label">最后登录时间</label>
            <div class="label_key"><?= DateHelper::dateTime($model['last_time'])?></div>
        </div>
        <div class="layui-col-xs12"><label class="label">最后登录IP</label>
            <div class="label_key"><?= $model['last_ip']?></div>
        </div>
        <div class="layui-col-xs12"><label class="label">最后登录设备</label>
            <div class="label_key"><?= \common\models\member\Member::$device[$model['last_device']]?></div>
        </div>
        <div class="layui-col-xs12"><label class="label">签&nbsp;&nbsp;名</label>
            <div class="block">
                <div class="label_key"><?= $model['sign']?></div>
            </div>
        </div>
        <div class="layui-col-xs12"><label class="label">发红包</label>
            <div class="block">
                <input type="checkbox" lay-skin="switch" lay-text="开启|关闭" <?php if ($model['allow_send_hongbao']) echo 'checked'?> lay-filter="allow_send_hongbao">
            </div>
        </div>
        <div class="layui-col-xs12"><label class="label">抢红包</label>
            <div class="block">
                <input type="checkbox" lay-skin="switch" lay-text="开启|关闭" <?php if ($model['allow_get_hongbao']) echo 'checked'?> lay-filter="allow_get_hongbao">
            </div>
        </div>
        <div class="layui-col-xs12"><label class="label">群内发言</label>
            <div class="block">
                <input type="checkbox" lay-skin="switch" lay-text="开启|关闭" <?php if ($model['gag_status']) echo 'checked'?> lay-filter="gag_status">
            </div>
        </div>
    </div>

</div>
</body>
<script src="/resources/plugins/layui/layui.js"></script>
<script>
    layui.use(['form', 'jquery', 'layer'], function(){
        var form = layui.form, $ = layui.jquery, layer = layui.layer;
        var csrf = '<?= Yii::$app->request->getCsrfToken()?>';
        form.on('switch(allow_send_hongbao)', function(data){
            $.ajax({
                headers: {
                    'X-CSRF-Token': csrf
                },
                type: "post",
                data: {id: <?= $model['id']?>, type: data.elem.checked},
                url: 'switch-send-red-pack',
                success: function (data) {
                    layer.msg(data.message)
                },
                error: function (e) {
                    layer.msg(e.responseJSON.message)
                }
            });
        });

        form.on('switch(allow_get_hongbao)', function(data){
            $.ajax({
                headers: {
                    'X-CSRF-Token': csrf
                },
                type: "post",
                data: {id: <?= $model['id']?>, type: data.elem.checked},
                url: 'switch-get-red-pack',
                success: function (data) {
                    layer.msg(data.message)
                },
                error: function (e) {
                    layer.msg(e.responseJSON.message)
                }
            });
        });

        form.on('switch(gag_status)', function(data){
            $.ajax({
                headers: {
                    'X-CSRF-Token': csrf
                },
                type: "post",
                data: {id: <?= $model['id']?>, type: data.elem.checked},
                url: 'switch-gag',
                success: function (data) {
                    layer.msg(data.message)
                },
                error: function (e) {
                    layer.msg(e.responseJSON.message)
                }
            });
        });
    });
</script>
</html>