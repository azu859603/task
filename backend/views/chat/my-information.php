<?php

use common\helpers\ImageHelper;

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
            height: 50px;
        }

        .layim-msgbox li {
            position: relative;
            margin-bottom: 10px;
            padding: 0 110px 10px 60px;
            line-height: 22px;
            border-bottom: 1px dotted #e2e2e2;
            width: 200px;
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
            height: 50px;
            color: #999;
        }

        .layim-msgbox li p em {
            font-style: normal;
            color: #FF5722;
        }

        .layim-msgbox-avatar {
            width: 50px;
            height: 50px;
        }

        .layim-msgbox .layui-btn-small {
            padding: 0 15px;
            margin-left: 5px;
        }

        .pt15 {
            padding-top: 15px;
        }

        .pt30 {
            padding-top: 30px;
        }

        .label {
            float: left;
            display: block;
            padding: 9px 5px 9px 20px;
            width: 40px;
            font-weight: 400;
            text-align: right;
        }

        .block {
            margin-left: 55px;
            min-height: 36px;
        }

        .layui-input, .layui-textarea {
            display: block;
            width: 90%;
            padding-left: 10px;
        }

        .noresize {
            resize: none;
        }

        .select {
            height: 38px;
            line-height: 38px;
            border: 1px solid #e6e6e6;
            background-color: #fff;
            border-radius: 2px;
        }
    </style>
</head>
<body>
<form class="layui-form" method="post" enctype="multipart/form-data">
    <div class="layui-form-item pt15">
        <div class="layim-msgbox" style="text-align: center">
            <img src="<?= ImageHelper::defaultHeaderPortrait($model['head_portrait'])?>" class="layui-circle layim-msgbox-avatar">
            <label for="avatar" class="layui-btn layui-btn-sm" style="position:absolute;right: 25px;top: 35px;">选择图片</label>
            <div style="display: none;">
                <input type="file" id="avatar" name="file" value="">
            </div>
        </div>
    </div>
    <?php if (\Yii::$app->controller->module->id === 'frontend'):?>
    <div class="layui-col-xs12 pt15">
        <div class="layui-col-xs6"><label class="label">钱&nbsp;&nbsp;包</label>
            <label class="label"><?= $model->account->user_money?></label>
        </div>
        <div class="layui-col-xs6"><label class="label">积&nbsp;&nbsp;分</label>
            <label class="label"><?= $model->account->user_integral?></label>
        </div>
    </div>
    <?php endif;?>
    <div class="layui-col-xs12 pt15">
        <div class="layui-col-xs6"><label class="label">昵&nbsp;&nbsp;称</label>
            <div class="block">
                <input type="text" class="layui-input" name="nickname" lay-verify="required" autocomplete="off" value="<?= $model->nickname?>">
            </div>
        </div>
        <div class="layui-col-xs5"><label class="label">性&nbsp;&nbsp;别</label>
            <div class="block" style="width: 123px">
                <select name="gender" class="select">
                    <option value="1" <?= $model->gender == 1 ? 'selected' : ''?>>男</option>
                    <option value="2" <?= $model->gender == 2 ? 'selected' : ''?>>女</option>
                    <option value="0" <?= $model->gender == 0 ? 'selected' : ''?>>保密</option>
                </select>
            </div>
        </div>
    </div>
    <div class="layui-col-xs12 pt15"><label class="label">手&nbsp;&nbsp;机</label>
        <div class="block">
            <input type="text" class="layui-input" value="<?= $model->mobile?>" name="mobile">
        </div>
    </div>
    <div class="layui-col-xs12 pt15"><label class="label">签&nbsp;&nbsp;名</label>
        <div class="block">
            <textarea name="sign" placeholder="请输入内容" class="layui-textarea noresize"><?= $model->sign?></textarea>
        </div>
    </div>
    <div class="layui-form-item pt30">

        <div style="text-align: center">
            <button class="layui-btn" lay-submit="" lay-filter="*">保存</button>
            <button type="button" id="close" class="layui-btn layui-btn-primary">关闭</button>
        </div>
    </div>
</form>
</body>
<script src="/resources/plugins/layui/layui.js"></script>
<script>
    layui.use(['form', 'jquery', 'layer'], function () {
        var form = layui.form, $ = layui.jquery, layer = layui.layer;
        var imgFile;
        $('#avatar').on('change', function() {
            imgFile = this.files[0];
            var reader = new FileReader();
            reader.readAsDataURL(imgFile);
            // 接受 jpeg, jpg, png 类型的图片
            if (/\/(?:jpeg|jpg|png|gif)/i.test(imgFile.type)){
                reader.onload = function (e) {
                    $('.layim-msgbox-avatar').attr('src', e.target.result);
                };
            } else {
                layer.msg('不支持的文件格式', function(){

                });
            }
        });

        $("#close").click(function(){
            var index = parent.layer.getFrameIndex(window.name);
            parent.layer.close(index);
        });
    });
</script>
</html>