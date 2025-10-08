<?php

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>聊天记录</title>

  <link rel="stylesheet" href="/resources/plugins/layui/css/layui.css">
  <link rel="stylesheet" href="/resources/css/contextMenu.css">
  <style>
    body .layim-chat-main{height: auto;padding-bottom: 45px;}
    .lay_page{position: fixed;bottom: 0;margin: 0 10px;background: #fff;width: 100%;}
    .layui-laypage{width: 105px;margin:0 auto;display: block}
  </style>
</head>
<body>

<div class="layim-chat-main pb45">
  <ul id="LAY_view"></ul>
</div>

<div class="lay_page" id="LAY_page" >
    <div class="layui-box layui-laypage layui-laypage-default" id="layui-laypage-1">
        <a href="javascript:;" class="layui-laypage-prev" data-page="0"><i class="layui-icon"></i></a>
        <a href="javascript:;" class="layui-laypage-next" data-page="2"><i class="layui-icon"></i></a>
    </div>
</div>


<textarea title="消息模版" id="LAY_tpl" style="display:none;">
{{# layui.each(d.data, function(index, item){ }}
  {{# if(item.from == '0'){ }}
      <li class="layim-chat-system" data-id={{ item.msg_id }}><cite>{{ item.timestamp }}</cite><span>{{ layui.layim.content(item.content, item.msgType) }}</span></li>
  {{# } else { }}
    {{# if(item.id == parent.layui.layim.cache().mine.id){ }}
    <li class="layim-chat-mine" data-id={{ item.msg_id }}><div class="layim-chat-user"><img src="{{ item.avatar }}"><cite><i>{{ item.timestamp }}</i><span>{{ item.username }}</span></cite></div><div class="layim-chat-text">{{ layui.layim.content(item.content, item.msgType) }}</div></li>
    {{# } else { }}
        {{# if(item.id !== undefined) { }}
            <li data-id={{ item.msg_id }}><div class="layim-chat-user" data-id="{{item.id}}" {{}}><img src="{{ item.avatar }}"><cite><span>{{ item.username }}</span><i>{{item.timestamp }}</i>{{# if (item.level){ }}<span class="layui-badge">{{item.level }}</span>{{# }}}</cite></div><div class="layim-chat-text">{{ layui.layim.content(item.content, item.msgType) }}</div></li>
        {{# } else { }}
            <li class="layim-chat-system"><span>{{ layui.layim.content(item.content, item.msgType) }}</span></li>
        {{# } }}
    {{# } }} 
  {{# }  
}); }}
</textarea>

<!-- 
上述模版采用了 laytpl 语法，不了解的同学可以去看下文档：http://www.layui.com/doc/modules/laytpl.html
-->

<script src="/resources/plugins/layui/layui.js"></script>
<script src="/resources/js/redpackage_manager.js"></script>
<script>
  layui.use(['layim'], function () {
    var layer = layui.layer
    ,laytpl = layui.laytpl
    ,$ = layui.jquery;
    var url = 'chat-log-detail';
    function formatDate(nS) {
      return new Date(parseInt(nS)).toLocaleString();
    }

    function getDetail(params) {
        $.get(url+ location.search, params, function(res){
            if(res.code != 0){
                return layer.msg(res.message);
            }
            layui.each(res.data, function(index, item){
                res.data[index]['timestamp'] =  formatDate(item.timestamp);
            });
            var html = laytpl(LAY_tpl.value).render({
                data: params.second_page ? res.data : res.data.reverse()
            });
            $('#LAY_view').html(html);
        });
    }

    // 查看上一页
    $('.layui-laypage-prev').on('click', function () {
        var top_id = $('#LAY_view').find('li:first').data('id');
        var params = {top_id:top_id};
        getDetail(params);
    });

    // 查看下一页
    $('.layui-laypage-next').on('click', function () {
        var bottom_id = $('#LAY_view').find('li:last').data('id');
        var count = $('#LAY_view').find('li').length;
        if (bottom_id && count !== 20) {
            var params = {bottom_id:bottom_id, second_page: true};
        } else {
            var params = {bottom_id:bottom_id};
        }
        getDetail(params);
    });

    getDetail([]);
  });
</script>
</body>
</html>