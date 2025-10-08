<?php
use common\helpers\DateHelper;

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>在线计划</title>

  <link rel="stylesheet" href="/resources/plugins/layui/css/layui.css">
  <style>
    .line-style {
        white-space: pre-line;
        padding-top: 0;
    }
    .plan-time {
        position: relative;
        top: -15px;
    }
  </style>
</head>
<body>

<div style="margin: 20px 15px;">
    <?php foreach ($model as $v): ?>
    <blockquote class="layui-elem-quote line-style">
        <span class="plan-time"><?= DateHelper::dateTime($v->updated_at)?></span>
        <?= $v->content?>
    </blockquote>
    <?php endforeach;?>
</div>

</body>
</html>