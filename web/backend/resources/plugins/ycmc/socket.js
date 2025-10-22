WEB_SOCKET_SWF_LOCATION = "/backend/resources/plugins/ycmc/WebSocketMain.swf";
var WEB_SOCKET_DEBUG = true;
var WEB_SOCKET_SUPPRESS_CROSS_DOMAIN_SWF_ERROR = true;
var socket_ping_itv; // 心跳定时器
var ws; //websocket实例
var lockReconnect = false; //避免重复连接

// var my_host = document.domain;
// var my_domain = my_host.split('.');
// if (my_domain.length === 3) {
//     my_host = my_domain[1] + '.' + my_domain[2];
// }
// var url = (location.protocol === 'https:' ? 'wss:' : 'ws:') + my_host + ":8585";
var url = 'ws://un.xj6666.xyz:8585';

// 创建socket连接
function createWebSocket(url) {
    try {
        ws = new WebSocket(url);
        socketHandle();
    } catch (e) {
        reconnect(url);
    }
}


// 重连
function reconnect() {
    if (lockReconnect) return;
    lockReconnect = true;
    //没连接上会一直重连，设置延迟避免请求过多
    setTimeout(function () {
        createWebSocket(url);
        lockReconnect = false;
    }, 2000);
}

function socketHandle() {
    ws.onerror = function () {
        reconnect(url);
    };
    if (ws.readyState == 3) {
        reconnect(url);
    }
    ws.onclose = function () {
        reconnect(url);
    };
    ws.onopen = function () {
        // console.log('连接成功');
        // 发送心跳
        if (socket_ping_itv) {
            clearInterval(socket_ping_itv);
        }
        socket_ping_itv = setInterval(function () {
            ws.send('ping');
        }, 10000);
    };
    ws.onmessage = function (e) {
        // json数据转换成js对象
        var data = eval("(" + e.data + ")");
        var type = data.type || '';
        console.log(data);
        if (type == 'init') {
            ws.send(JSON.stringify({type: 'init'}));
            binding(data);
        } else {
            getMessage(data, type);
        }
    };
}

// 绑定后台用户
function binding(data) {
    // console.log(data);
    $.ajax({
        type: 'post',
        data: {
            'client_id': data.data.client_id
        },
        url: '/backend/base/member/binding',
        success: function (result) {
            if (result.code == 200) {
                console.log(result.message);
            } else {
                console.log(result.message);
            }
        },
    });
}

//接收信息
function getMessage(data, type) {
    $('.iframe_music').remove();
    var html = '<video controls="" class="iframe_music" autoplay="" style="display: none"><source src="/backend/resources/plugins/ycmc/' + type + '.wav" type="audio/x-wav"></video>';
    $('body').append(html);//src='声音地址'
    // 底部消息通知(备用)
    // var config = {
    //     title: data.data.title,//通知标题部分  默认 新消息   可选
    //     body: data.data.content,//通知内容部分
    //     onclick: function () {
    //         window.location.href =  data.data.jump_url;
    //     },
    // };
    // new dToast(config);
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "progressBar": true,
        "preventDuplicates": false,
        "positionClass": "toast-top-right",
        "onclick": function (e) {
            window.openConTab($('<a class="J_menuItem" id="jumpButton" href="' + this.jump_url + '" data-index=""><i class="fa  rf-i"></i><span>' + this.title + '</span></a>'));
        },
        "showDuration": "400",
        "hideDuration": "1000",
        "timeOut": "1000",//设置自动关闭时间
        "extendedTimeOut": "0",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut",
    };
    toastr.options.title = data.data.title;
    toastr.options.jump_url = data.data.jump_url;
    toastr.success(timestampToTime(data.data.created_at), data.data.content);
}

// 转换时间戳
function timestampToTime(timestamp) {
    var date = new Date(timestamp * 1000);//时间戳为10位需*1000，时间戳为13位的话不需乘1000
    var Y = date.getFullYear() + '-';
    var M = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) + '-';
    var D = (date.getDate() < 10 ? '0' + date.getDate() : date.getDate()) + ' ';
    var h = (date.getHours() < 10 ? '0' + date.getHours() : date.getHours()) + ':';
    var m = (date.getMinutes() < 10 ? '0' + date.getMinutes() : date.getMinutes()) + ':';
    var s = (date.getSeconds() < 10 ? '0' + date.getSeconds() : date.getSeconds());
    return Y + M + D + h + m + s;
}

function messageInit() {
    get_message_count()
    setInterval(() => {
        get_message_count()
    }, 30000)

}

// 绑定后台用户
function get_message_count() {
    $.ajax({
        type: 'get',
        url: '/backend/base/member/get-message',
        success: function (result) {
            if (result.code == 200) {
                // var member_count = document.getElementById('member_count')
                // member_count.innerText =result.data.member_count;
                if (result.data.recharge_count > 0) {// 判断充值提示
                    getMessage({
                        "data": {
                            'title': '充值审核',
                            'content': '您有新的充值请求，请及时处理！',
                            'jump_url': '/backend/member/recharge-bill/index',
                            'created_at': result.data.created_at,
                        }
                    }, 'recharge');
                }
                if (result.data.withdraw_count > 0) {// 判断提现提示
                    setTimeout(() => {
                        getMessage({
                            "data": {
                                'title': '提现审核',
                                'content': '您有新的提现请求，请及时处理！',
                                'jump_url': '/backend/member/withdraw-bill/index',
                                'created_at': result.data.created_at,
                            }
                        }, 'withdraw');
                    }, 10000)
                }
                if (result.data.realname_count > 0) {// 判断提示
                    setTimeout(() => {
                        getMessage({
                            "data": {
                                'title': '实名认证',
                                'content': '您有新的认证审核，请及时处理！',
                                'jump_url': '/backend/member/realname-audit/index',
                                'created_at': result.data.created_at,
                            }
                        }, 'realname');
                    }, 20000)
                }
            }
        },
    });
}

//初始化
$(document).ready(function () {
    // socket推送消息
    // createWebSocket(url);
    // 轮训推送
    messageInit();
});