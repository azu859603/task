<?php
use common\helpers\DateHelper;

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>最新活动</title>

  <link rel="stylesheet" href="/resources/plugins/layui/css/layui.css">
</head>
<body>
<div style="margin: 20px 15px;">
    <?php if (!$model):?>
        <blockquote class="layui-elem-quote layui-quote-nm">暂无数据</blockquote>
    <?php else:?>
        <?php foreach ($model as $v): ?>
        <blockquote class="layui-elem-quote layui-quote-nm">
            <span class="plan-time"><?= DateHelper::dateTime($v->created_at)?></span>
            <br>
            <?= $v->content?>
            <?php if ($v->img):?>
                <br>
                <img src="<?= $v->img?>" style="max-width: 320px;">
            <?php endif;?>
            <?php if ($v->url):?>
                <br>
                <a href="<?= $v->url?>" class="layui-input-block" target="_blank">查看详情</a>
            <?php endif;?>
        </blockquote>
        <?php endforeach;?>
    <?php endif;?>
</div>
</body>
<script src="/resources/plugins/layui/layui.js"></script>
</html>