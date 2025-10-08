layui.define('jquery', function (exports) {
    "use strict";
    var $ = layui.jquery;
    $('body').on('click', '.redpack', function () {
        var id = $(this).attr('id');
        var avatar = $(this).parent().prev().children('img').attr('src');
        var nickname = $(this).parent().prev().children('cite').children('span').text();
        var type = $(this).data('type');
        var content = '<div class="redpack-big" id="'+id+'"><div class="topcontent-big"><div class="avatar-div"><img src="'+avatar+'" class="hongbao-avatar"></div><div class="hongbao-title">'+nickname+'</div><p style="margin-top:20px;font-size: 18px;color:#ffffff;">';
        if (type == 1) {
            content += '</p></div><div class="hongbao-footer"><div style="padding-top:20%;color:#fa9d3b;"><h4 class="shoushi shouqi">看看大家的运气>></h4></div></div></div>';
            layer.open({
                type: 1,
                title: false,
                closeBtn: 1,
                skin: 'layui-layer-nobg',
                shadeClose: false,
                area: ['280px', '380px'],
                zIndex: layer.zIndex,
                content: content,
                success: function(layero) {
                    layer.setTop(layero);
                    layero.css({"border-radius":"10px"});
                }
            });
        }
        if (type == 2) {
            $.post("/backend/chat/lucky-redpackage-detail", {id: id}, function (result) {
                if (result.code == 200) {
                    var content = '<div class="redpack-big" id="'+id+'"><div class="topcontent-big"><div class="avatar-div"><img src="'+avatar+'" class="hongbao-avatar"></div><div class="hongbao-title">'+nickname+'</div><p style="margin-top:20px;font-size: 18px;color:#ffffff;">累计金额：'+result.data.total+'<p class="hongbao-detail" style="color: #ffffff">起始金额：<span id="need_money">'+result.data.start+'</span></div><div class="hongbao-footer"><div style="padding-top:20%;color:#fa9d3b;">';
                    if (result.data.is_get == 1) {
                        content += '已被抢走';
                    }
                    content += '</div></div></div>';
                    layer.open({
                        type: 1,
                        title: false,
                        closeBtn: 1,
                        skin: 'layui-layer-nobg',
                        shadeClose: false,
                        area: ['280px', '380px'],
                        zIndex: layer.zIndex,
                        content: content,
                        success: function(layero) {
                            layer.setTop(layero);
                            layero.css({"border-radius":"10px"});
                        }
                    });
                }
            });
        }
    });

    $('body').on('click', '.shouqi', function() {
        var id = $(this).parents('.redpack-big').attr('id');
        var avatar = $(this).parent().parent().prev().children('.avatar-div').children('img').attr('src');
        var nickname = $(this).parent().parent().prev().children('.hongbao-title').html();
        layer.close(layer.index);
        $.post("/backend/chat/redpackage-got-list", {id: id}, function (result) {
            var content = '<div class="redpack-big"><div class="topcontent-big-list"><div class="avatar-div"><img src="'+avatar+'" class="hongbao-avatar"></div></div><div class="hongbao-content"><p style="padding-top:25px">'+nickname+'的红包 </p><div class="money">'+result.data.redpackage.total_amount+'<span style="color:#333;font-size:14px;">元</span></div><p style="text-align: left;background-color: #cccccc;color:#000;margin: 0 0 10px;">已领取'+result.data.redpackage.remain_count+'/'+result.data.redpackage.total_count+'个，共'+result.data.redpackage.remain_amount+'元 </p><div class="HBlist">';
            if (result.data.list_info) {
                $(result.data.list_info).each(function(i, t) {
                    content += '<div class="list-info"><div class="user-img"><img src="'+t.avatar+'" width="35" height="35"></div><div class="list-right"><div class="list-right-top"><div class="user-name fl f14">'+t.nickname+'</div><div class="user-money fr f14">'+t.amount+'元 </div><div class="user-time">'+layui.data.date(t.get_time * 1000)+'</div></div></div></div>'
                })
            }
            content += '</div></div></div>';
            layer.open({
                type: 1,
                title: false,
                closeBtn: 1,
                skin: 'layui-layer-nobg',
                shadeClose: false,
                area: ['280px', '380px'],
                content: content,
                zIndex: layer.zIndex,
                success: function(layero) {
                    layer.setTop(layero);
                    layero.css({"border-radius":"10px"});
                }
            });
        }).error(function (res) {
            layer.msg(res.responseJSON.message, {zIndex: layer.zIndex});
        });
    });

    $('body').on('click', '.layim-chat-user img', function () {
        var id = $(this).parent().data('id');
        if (id && id.match('member')) {
            return layer.open({
                type: 2
                ,title: '会员资料'
                ,shade: false
                ,maxmin: false
                ,area: ['400px', '670px']
                ,skin: 'layui-box layui-layer-border'
                ,resize: true
                ,content: 'information?id='+id
                ,zIndex: layer.zIndex
                ,success: function(layero){
                    layer.setTop(layero);
                }
            });
        }
    });
});
