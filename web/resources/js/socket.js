layui.define(['jquery', 'layer', 'contextMenu', 'form', 'layim'], function (exports) {
    var contextMenu = layui.contextMenu;
    var $ = layui.jquery;
    var layer = layui.layer;
    var cachedata = layui.layim.cache();
    var socket_ping_itv; // 心跳定时器
    var ws; //websocket实例
    var lockReconnect = false; //避免websocket重复连接
    var layim = layui.layim;
    var is_reconnect = 0;
    var disconnect_time = 0;
    var im_is_init = false;
    var tt;
    var conf = {
        myId: null,
        myType: null,
    };

    var socket = {
        config: function (options) {
            conf = $.extend(conf, options);
            // this.createWebSocket(); //创建websocket连接
        },
        socketHandle: function () {
            ws.onerror = function () {
                console.log('通信网络出现异常，尝试重连');
                socket.reconnect();
            };
            if (ws.readyState == 3) {
                console.log('通信网络出现异常，尝试重连');
                socket.reconnect();
            }
            ws.onclose = function () {
                console.log('通信网络已断开，尝试重连');
                if (disconnect_time == 0) {
                    disconnect_time = Math.round(new Date().getTime() / 1000).toString();
                }
                is_reconnect = 1;
                socket.reconnect();
            };
            ws.onopen = function () {
                if (im_is_init == false) {
                    im.init();
                }
                console.log('连接成功');
                // 发送心跳
                if (socket_ping_itv) {
                    clearInterval(socket_ping_itv);
                }
                socket_ping_itv = setInterval(function () {
                    ws.send('ping');
                }, 24000);
            };
            ws.onmessage = function (e) {
                var dataInfo = eval("(" + e.data + ")");
                var type = dataInfo.type || '';
                var data = JSON.parse(e.data);
                if (type == 'cmd') {
                    switch (data.order) {
                        case 'init':
                            ws.send(JSON.stringify({
                                type: 'init',
                                uid: conf.myType + '_' + conf.myId
                            }));
                            $.post('chat/bind', {client_id: data.client_id}, function (res) {
                                if (res.code === "200") {
                                    if (is_reconnect == 1 && disconnect_time != 0) {
                                        $.post('chat/reconnect', {
                                            client_id: data.client_id,
                                            disconnect_time: disconnect_time
                                        }, function (res) {
                                        });
                                    }
                                    disconnect_time = 0;
                                    is_reconnect = 0;
                                }
                            }, "JSON");
                            return false;
                        case 'online':
                        case 'offline':
                            return false;
                        case 'plan': //计划
                            if ($('#plan_box_' + data.id).length > 0) {
                                $('#plan_box_' + data.id).children('iframe')[0].contentWindow.location.reload();
                            } else {
                                layim.voice();
                                $('.layui-layim-tool .layim-tool-plan .layui-badge-dot').removeClass('layui-hide');
                            }
                            var thisChat = layim.thisChat();
                            var plan = $('.layim-plan-list');
                            if (cachedata.mine.id.match('manager') && thisChat && thisChat.data.id == data.id) {
                                $.get('chat/plan', {id: thisChat.data.id}, function (result) {
                                    var plan_content = '';
                                    if (result.data) {
                                        layui.each(result.data, function (index, item) {
                                            plan_content += '<blockquote class="layui-elem-quote line-style"><p>' + turn_time(item.updated_at) + '</p>' + item.content + '</blockquote>';
                                        });
                                    }
                                    plan.html(plan_content);
                                });
                            }
                            return false;
                        case 'logout':
                            ws.close();
                            var cache = layui.layim.cache();
                            layui.data('layim', {
                                key: cache.mine.id
                                , value: ''
                            });
                            window.location.reload();
                            return false;
                        case 'level_up':
                            if (data.uid === cachedata.mine.id) cachedata.mine.level = data.data === '无' ? '' : data.data;
                            if (cachedata.mine.id.match('manager')) {
                                // 移动等级变更的会员
                                var list_item = $('.layim-list-friend li .layui-layim-list .layim-friend' + data.uid);
                                if (list_item.length > 0) {
                                    var list_data = cachedata.friend[list_item.data('index')].list[list_item.index()],
                                        groupid = list_item.parent().parent().data('id'),
                                        unReadCount = list_data.unReadCount, sign = list_item.find('p').text();
                                    layim.removeList({
                                        type: 'friend'
                                        , id: data.uid
                                    });
                                    layim.addList({
                                        type: 'friend'
                                        , avatar: list_data.avatar
                                        , username: list_data.username
                                        , groupid: groupid
                                        , id: list_data.id
                                        , sign: sign
                                        , status: list_data.status
                                        , unReadCount: unReadCount
                                        , level: data.data
                                    });
                                    if (list_data.status === 'online') {
                                        layim.sortSetOnline(list_data.id);
                                    }
                                }
                            }
                            break;
                        case 'room_status_change' :
                            var _index = 0;
                            var name = data.room_name;
                            if (data.data === "2") {
                                // 房间禁言，修改信息
                                name += '(禁言)';
                            }
                            layui.each(cachedata.group, function (index, item) {
                                if (item.id == data.id) {
                                    _index = index;
                                    cachedata.group[index].groupstatus = data.data;
                                    layui.layim.cache().group[_index].groupstatus = data.data;
                                    cachedata.group[_index].groupname = name;
                                    layui.layim.cache().group[_index].groupname = name;
                                    cachedata.group[_index].name = name;
                                    layui.layim.cache().group[_index].name = name;
                                }
                            });
                            layui.each($('.layim-group' + cachedata.group[_index].id), function (index, item) {
                                if (index === 3) {
                                    var ht = $(item).next().html().replace(cachedata.group[_index].name, name);
                                    $(item).next().html(ht);
                                } else if (index === 2) {
                                    $(item).find('span:last').text(name);
                                } else {
                                    $(item).find('span').text(name);
                                }
                            });
                            break;
                        case 'gag':
                            if (data.uid == cachedata.mine.id) {
                                cachedata.mine.gag_status = data.status;
                                layui.layim.cache().mine.gag_status = data.status;
                            }
                            break;
                        case 'revoke_message':
                            var thisChat = layim.thisChat();
                            var _index = data.group_type + data.id;
                            if (thisChat) {
                                var msg_item = $('.layim-chat-main ul li[data-cid=' + data.msg_id + ']');
                                msg_item.length > 0 ? msg_item.remove() : '';
                            }
                            if (data.from_id == cachedata.mine.id) {
                                if (data.group_type == 'friend') {
                                    _index = data.group_type + data.to_id;
                                    id = data.to_id;
                                }
                            }
                            // 删除缓存
                            var local = layui.data('layim')[cachedata.mine.id];
                            layui.each(local.chatlog[_index], function (index, item) {
                                if (item.cid == data.msg_id) {
                                    local.chatlog[_index].splice(index, 1);
                                }
                            });
                            layui.data('layim', {
                                key: cachedata.mine.id
                                , value: local
                            });
                            im.showLastContentOne(_index, data.group_type);
                            break;
                        case 'shield_message':
                            var thisChat = layim.thisChat();
                            var _index = data.group_type + data.id;
                            if (thisChat) {
                                var msg_item = $('.layim-chat-main ul li[data-cid=' + data.msg_id + ']');
                                msg_item.length > 0 ? msg_item.remove() : '';
                            }
                            // 删除缓存
                            var local = layui.data('layim')[cachedata.mine.id];
                            layui.each(local.chatlog[_index], function (index, item) {
                                if (item.cid == data.msg_id) {
                                    local.chatlog[_index].splice(index, 1);
                                }
                            });
                            layui.data('layim', {
                                key: cachedata.mine.id
                                , value: local
                            });
                            im.showLastContentOne(_index, data.group_type);
                            return false;
                        case 'set_top':
                            var is_set = $('.layim-chat-box .layim-chat-other .layim-group' + data.id);
                            if (is_set) {
                                is_set.parent().parent().parent().find('.layim-chat-main .layim-chat-top-msg span').html(layui.data.content(data.content, data.content_type));
                                var top_msg_box = is_set.parent().parent().parent().find('.layim-chat-top-msg');
                                if (!top_msg_box.is(':visible')) {
                                    top_msg_box.show();
                                }
                            }
                            layui.each(layui.layim.cache().group, function (index, item) {
                                if (item.id == data.id) {
                                    cachedata.group[index].top_msg = data.content;
                                    layui.layim.cache().group[index].top_msg = data.content;
                                    cachedata.group[index].top_msg_type = data.content_type;
                                    layui.layim.cache().group[index].top_msg_type = data.content_type;
                                }
                            });
                            break;
                        case 'cancel_top':
                            var is_set = $('.layim-chat-box .layim-chat-other .layim-group' + data.id);
                            if (is_set) {
                                is_set.parent().parent().parent().find('.layim-chat-main .layim-chat-top-msg span').html("");
                                is_set.parent().parent().parent().find('.layim-chat-top-msg').hide();
                            }
                            layui.each(layui.layim.cache().group, function (index, item) {
                                if (item.id == data.id) {
                                    cachedata.group[index].top_msg = "";
                                    layui.layim.cache().group[index].top_msg = 0;
                                    cachedata.group[index].top_msg_type = "";
                                    layui.layim.cache().group[index].top_msg_type = 0;
                                }
                            });
                            break;
                        case 'someone_leave':
                            // layim.removeList({
                            //     'type': 'friend',
                            //     'id': data.id
                            // });
                            // if (data.id == cachedata.mine.id) {
                            //     ws.close();
                            //     layui.data('layim', {
                            //         key: cachedata.mine.id
                            //         ,value: ''
                            //     });
                            //     window.location.reload();
                            // }
                            // //删除 DOM
                            // var dom = $('.layim-list-history li.layim-friend'+data.id);
                            // if (dom.length > 0) dom.remove();
                            // var hisElem = $('#layui-layim').find('.layim-list-history');
                            // if(hisElem.find('li').length === 0){
                            //     var none = '<li class="layim-null">暂无历史会话</li>';
                            //     hisElem.html(none);
                            // }
                            return false;
                        case 'someone_come':
                            // if (cachedata.mine.id.match('member') && data.role == 'manager') {
                            //     layim.addList({
                            //         type: 'friend'
                            //         ,avatar: data.avatar
                            //         ,username: data.username
                            //         ,groupid: 1
                            //         ,id: 'manager_' + data.id
                            //         ,sign: data.sign
                            //         ,status:data.status ? data.status : 'online'
                            //     });
                            // }
                            // if (cachedata.mine.id.match('manager') && data.role == 'member') {
                            //     layim.addList({
                            //         type: 'friend'
                            //         ,avatar: data.avatar
                            //         ,username: data.username
                            //         ,groupid: data.group_id
                            //         ,id: 'member_' + data.id
                            //         ,sign: data.sign
                            //         ,status:data.status ? data.status : 'online'
                            //     });
                            // }
                            return false;
                    }
                    layim.getMessage({
                        system: true //系统消息
                        , id: data.id //聊天窗口ID
                        , type: data.group_type //聊天窗口类型
                        , content: data.content
                    });
                } else {
                    if (cachedata.mine && im_is_init == true) {
                        if (data.from_id === cachedata.mine.id) {
                            if (data.msg_type === "1" || data.msg_type === "2") return;
                        }
                        im.defineMessage(data);
                    }
                }
            };
        },
        reconnect: function () {
            if (lockReconnect) return;
            lockReconnect = true;
            // 没连接上会一直重连，设置延迟避免请求过多
            tt && clearTimeout(tt);
            tt = setTimeout(function () {
                socket.createWebSocket();
                lockReconnect = false;
            }, 4000);
        },
        createWebSocket: function () {
            try {
                ws = new WebSocket(WebIM.config.wsUrl);
                socket.socketHandle();
            } catch (e) {
                socket.reconnect();
            }
        }
    };

    var im = {
        init: function () {
            this.initListener(); //初始化事件监听
        },
        register: function () {
            if (layim) {
                // 监听layim建立就绪
                layim.on('ready', function (res) {
                    im.contextMenu();
                    im.showLastContent();
                    im_is_init = true;
                });
                // 监听签名修改
                layim.on('sign', function (value) {
                    $.post('chat/change-sign', {sign: value}, function (data) {
                        layer.msg(data.message);
                    }).error(function (res) {
                        layer.msg(res.responseJSON.message);
                    });
                });

                // 监听聊天窗口的切换
                layim.on('chatChange', function (res) {
                    // 清除未读角标
                    var type = res.data.type;
                    var list_unread = $('.layim-' + res.data.type + res.data.id + '[data-type="' + res.data.type + '"]').find('.layim-msg-status');
                    var left_unread = $('.layim-chat-list').find('.layim-chatlist-' + res.data.type + res.data.id).find('.layim-msg-status');
                    if (list_unread.text() > 0 && type === 'friend') {
                        // 设置已读
                        $.post('chat/read', {type: type, id: res.data.id});
                    }
                    list_unread.hide();
                    list_unread.text(0);
                    left_unread.hide();
                    left_unread.text(0);
                    var history_unread = $('.layim-' + res.data.type + res.data.id + '[data-type="history"]').find('.layim-msg-status');
                    history_unread.hide();
                    history_unread.text(0);
                    var plan = $('.layim-plan-list');
                    if (type === 'friend') {
                        plan.hide();
                        // 标注会员状态
                        if (res.data.status === 'online') {
                            layim.setChatStatus('<span style="color:#FF5722;">在线</span>');
                        } else {
                            layim.setChatStatus('<span style="color:#444;">离线</span>');
                        }
                        layim.setSign(res.data.sign);
                        // 获取对方最新信息
                        $.get('chat/get-info?id=' + res.data.id, function (re) {
                            var local = layui.data('layim')[cachedata.mine.id];
                            var thisChat = layim.thisChat();
                            var member_dom = $('body .layim-chat-list .layim-friend' + res.data.id);
                            if (re.data.nickname !== res.data.username) {
                                // 更新昵称
                                local.history['friend' + res.data.id].username = re.data.nickname;
                                thisChat.elem.find('.layim-chat-username').text(re.data.nickname);
                                $('body .layim-list-friend').find('li.layim-friend' + res.data.id).children('span:first').text(re.data.nickname);
                                $('body .layim-list-history').find('li.layim-friend' + res.data.id).children('span:first').text(re.data.nickname);
                                if (member_dom.length > 0) {
                                    member_dom.find('span').text(re.data.nickname);
                                }
                            }
                            if (re.data.avatar !== res.data.avatar) {
                                // 更新头像
                                local.history['friend' + res.data.id].avatar = re.data.avatar;
                                thisChat.elem.find('.layim-friend' + res.data.id).attr('src', re.data.avatar);
                                $('body .layim-list-friend').find('li.layim-friend' + res.data.id).children('img').attr('src', re.data.avatar);
                                $('body .layim-list-history').find('li.layim-friend' + res.data.id).children('img').attr('src', re.data.avatar);
                                if (member_dom.length > 0) {
                                    member_dom.find('img').attr('src', re.data.avatar);
                                }
                            }
                            if (re.data.sign !== res.data.sign) {
                                // 更新签名
                                local.history['friend' + res.data.id].sign = re.data.sign;
                                thisChat.elem.find('.layim-sign').text(re.data.sign);
                                $('body .layim-list-friend').find('li.layim-friend' + res.data.id).children('p').text(re.data.sign);
                                $('body .layim-list-history').find('li.layim-friend' + res.data.id).children('p').text(re.data.sign);
                            }
                            layui.data('layim', {
                                key: cachedata.mine.id
                                , value: local
                            });
                        });
                    } else {
                        if (cachedata.mine.id.match('manager')) {
                            // 获取计划
                            // $.get('chat/plan', {id: res.data.id}, function (result) {
                            //     var plan_content = '';
                            //     if (result.data) {
                            //         layui.each(result.data, function (index, item) {
                            //             plan_content += '<blockquote class="layui-elem-quote line-style"><p>' + turn_time(item.updated_at) + '</p>' + item.content;
                            //             if (item.img.length > 0) {
                            //                 plan_content += '<img src="' + item.img + '" style="width: 248px">';
                            //             }
                            //             plan_content += '</blockquote>';
                            //         });
                            //     }
                            //     plan.html(plan_content);
                            // });
                        }
                    }
                });

                layim.on('sendMessage', function (data, cacheMessage) { //监听发送消息
                    if (data.to.type == 'friend') {
                        im.sendMsg(data, cacheMessage);
                    } else {
                        var status = cachedata.group[0].groupstatus;
                        if (status == 0) {
                            im.popMsg(data, '当前为禁用状态，消息未发送成功！');
                            return false;
                        } else if (status == 2 && !cachedata.mine.id.match('manager')) {
                            im.popMsg(data, '当前为禁言状态，消息未发送成功！');
                            return false;
                        } else if (cachedata.mine.gag_status == 0) {
                            im.popMsg(data, '你已被禁言，消息未发送成功！');
                            return false;
                        }
                        im.sendMsg(data, cacheMessage);
                    }
                });
            }
        },
        // 显示最近一条消息
        showLastContent: function () {
            layui.each(cachedata.local.chatlog, function (index, item) {
                var last_msg = item[item.length - 1];
                if (last_msg) {
                    if (index.substr(0, 1) == "f") {
                        var item = $(".layim-list-friend > li > ul > li.layim-friend" + last_msg.id);
                    } else {
                        var item = $(".layim-list-group > li.layim-group" + last_msg.id);
                    }
                    item.children('p').text(im.matchContent(last_msg.content, last_msg.msgType));
                }
            });
        },
        showLastContentOne: function (id, type) {
            var chatlog = layui.data('layim')[cachedata.mine.id].chatlog[id];
            var last_msg = chatlog[chatlog.length - 1];
            if (type == "friend") {
                var item = $(".layim-list-friend > li > ul > li.layim-" + id);
            } else {
                var item = $(".layim-list-group > li.layim-" + id);
            }
            var unread_count = item.find('.layim-msg-status');
            if (last_msg) {
                item.children('p').text(im.matchContent(last_msg.content, last_msg.msgType));
                var new_unread_count = unread_count.text() - 1;
                new_unread_count = new_unread_count > 0 ? new_unread_count : 0;
                unread_count.text(new_unread_count);
                if (new_unread_count <= 0) {
                    unread_count.hide()
                }
                ;
            } else {
                unread_count.text(0);
                unread_count.hide();
                item.children('p').text('');
            }
        },
        contextMenu: function () { //定义右键操作
            //会员列表右键事件
            var my_spread = $('.layim-list-friend > li');
            my_spread.mousedown(function (e) {
                var data = {
                    contextItem: "context-friend", // 添加class
                    target: function (ele) { // 当前元素
                        $(".context-friend").attr("data-id", $(ele).attr('class').replace(/^layim-friend/ig, "")).attr("data-name", ele.find("span").html());
                        $(".context-friend").attr("data-img", ele.find("img").attr('src')).attr("data-type", 'friend').attr('data-index', ele.data('index'));
                        var friend_cache = cachedata.friend[ele.data('index')].list[ele.index()];
                        $(".context-friend").attr("data-status", friend_cache.status).attr('data-sign', friend_cache.sign);
                    },
                    menu: []
                };
                var isManager = cachedata.mine.id.match('manager');
                data.menu.push(im.menuChat());
                if (isManager) {
                    // data.menu.push(im.menuInfo());
                }
                data.menu.push(im.menuChatLog());
                $(".layim-list-friend >li > ul > li").not('.layim-null').contextMenu(data);
            });

            // 面板群组右键事件
            $(".layim-list-group > li").mousedown(function (e) {
                var data = {
                    contextItem: "context-group", // 添加class
                    target: function (ele) { // 当前元素
                        $(".context-group").attr("data-id", $(ele).attr('class').replace(/[^0-9]/ig, "")).attr("data-name", ele.find("span").html()).attr("data-img", ele.find("img").attr('src')).attr("data-type", 'group');
                    },
                    menu: []
                };
                data.menu.push(im.menuChat());
                // data.menu.push(im.menuInfo()); // 房间信息
                data.menu.push(im.menuChatLog());

                $(this).contextMenu(data);
            });
        },
        initListener: function () {
            layim.config({
                init: {
                    url: 'chat/init', data: {}
                },
                //获取群成员
                members: {
                    url: 'chat/group-members', data: {}
                }
                //上传图片接口
                , uploadImage: {
                    url: 'chat/image', type: 'post'
                }
                //获取历史记录
                , getChatLog: {
                    url: 'chat/chat-log', type: 'get' //默认post
                }
                , isAudio: false //开启聊天工具栏音频
                , isVideo: false //开启聊天工具栏视频
                , groupMembers: true
                //扩展工具栏
                // , tool: [{
                //         alias: 'code'
                //         , title: '代码'
                //         , icon: '&#xe64e;'
                //     }]
                , title: '短剧APP'
                , voice: 'voice.mp3'
                // , bulletin: 'chat/bulletin'
                // , active: 'chat/active'
                , copyright: true
                , initSkin: '2.jpg' //1-5 设置初始背景
                , notice: true //是否开启桌面消息提醒，默认false
                , systemNotice: false //是否开启系统消息提醒，默认false
                , chatLog: 'chat/chat-log' //聊天记录页面地址，若不开启，剔除该项即可
                , information: 'chat/information' //会员资料页面
                // , myInformation: 'chat/my-information' //我的资料页面
                // , plan: 'chat/plan' //计划页面
                // , rank: 'chat/rank' //排行榜
            });
            im.register();
        },
        //自定义消息，把消息格式定义为layim的消息类型
        defineMessage: function (message) {
            console.log(message)
            var data = {
                username: message.username //消息来源用户名
                , avatar: message.avatar //消息来源用户头像
                , id: message.id //消息的来源ID（如果是私聊，则是用户id，如果是群聊，则是群组id）
                , type: message.group_type //聊天窗口来源类型，从发送消息传递的to里面获取
                , content: message.content //消息内容
                , cid: message.cid //消息id，可不传。除非你要对消息进行一些操作（如撤回）
                , mine: false //是否我发送的消息，如果为true，则会显示在右方
                , fromid: message.from_id //消息的发送者id（比如群组中的某个消息发送者），可用于自动解决浏览器多窗口时的一些问题
                , timestamp: message.time * 1000 //服务端时间戳毫秒数。注意：如果你返回的是标准的 unix 时间戳，记得要 *1000
                , level: message.level
                , msgType: message.msg_type
                , nickname: message.nickname
            };


            if (message.group_type === "friend") {
                layim.addList({
                    type: 'friend'
                    , avatar: message.avatar
                    , username: message.username
                    , groupid: 0
                    , id: message.id
                    , sign: ""
                    , status: message.status
                    , level: message.level
                });
                // 将会话排序到最前
                layim.sortSetOnline(message.group_type + message.id);
                // 设为上线
                layim.setFriendStatus(message.id, 'online');
                // 更新最新消息
                $(".layim-list-friend > li > ul > li.layim-friend" + message.id).children('p').text(im.matchContent(message.content, message.msg_type));
            } else {
                $(".layim-list-group > li.layim-group" + message.id).children('p').text(im.matchContent(message.content, message.msg_type));
            }
            layim.getMessage(data);
            // 判断是否当前正在对话
            var this_chat = layim.thisChat();
            if (this_chat) {
                if (this_chat.data.id == data.id) {
                    if (message.group_type === 'friend') {
                        // 正在对话，设为已读
                        $.post('chat/read', {type: message.group_type, id: message.id});
                        return;
                    } else {
                        return;
                    }
                }
            }
            var un_read_dom = $('.layim-' + message.group_type + message.id + '[data-type="' + message.group_type + '"]').find('.layim-msg-status');
            // 未读条数
            var count = parseInt(un_read_dom.text()) + 1;
            if (un_read_dom.text() === "undefined") count = 1;
            count = count <= 99 ? count : '99+';
            un_read_dom.text(count);
            un_read_dom.show();
            var left_un_read_dom = $('.layim-chat-list').find('.layim-chatlist-' + message.group_type + message.id).find('.layim-msg-status');
            var left_count = parseInt(left_un_read_dom.text()) + 1;
            if (left_un_read_dom.text() === "undefined") left_count = 1;
            left_count = left_count <= 99 ? left_count : '99+';
            left_un_read_dom.text(left_count);
            left_un_read_dom.show();
        },
        sendMsg: function (data, cacheMessage) { //根据layim提供的data数据，进行解析
            $('.layui-show .layim-chat-main ul li:last .layim-chat-text').before('<i class="layui-icon layui-icon-loading layui-icon layui-anim layui-anim-rotate layui-anim-loop" style="position: relative; top: 35px"></i>');
            // 更新最新消息
            if (data.to.type === "friend") {
                $(".layim-list-friend > li > ul > li.layim-friend" + data.to.id).children('p').text(im.matchContent(data.mine.content, cacheMessage.msgType));
            } else {
                $(".layim-list-group > li.layim-group" + data.to.id).children('p').text(im.matchContent(data.mine.content, cacheMessage.msgType));
            }
            if (data.to.id == data.mine.id && data.to.roleType == conf.myType) {
                layer.msg('不能给自己发送消息');
                return;
            }
            var timestamp = data.mine.timestamp;
            $.post('chat/send-msg', {
                to: data.to.id,
                content: data.mine.content,
                type: data.to.type,
                msgType: cacheMessage.msgType,
                atArr: layim.atArr
            }, function (res) {
                var timestamp_dom = $('.layim-chat-mine .timestamp' + timestamp);
                timestamp_dom.prev('i').remove();// 清除loading
                if (res.code === "200") {
                    // id追加到消息
                    timestamp_dom.parent().attr("data-cid", res.data.cid);
                    cacheMessage.cid = res.data.cid;
                    layim.pushChatlog(cacheMessage);// 记录缓存到本地
                } else {
                    layer.msg(res.message, {
                        zIndex: layer.zIndex,
                        success: function (layero) {
                            layer.setTop(layero);
                        }
                    });
                    timestamp_dom.parent().remove();
                }
            });
        },

        getChatLog: function (data) {
            if (!cachedata.base.chatLog) {
                return layer.msg('未开启更多聊天记录');
            }
            var index = layer.open({
                type: 2
                , maxmin: true
                , title: '与 ' + data.name + ' 的聊天记录'
                , area: ['450px', '600px']
                , shade: false
                , skin: 'layui-box'
                , anim: 2
                , id: 'layui-layim-chatlog'
                , content: cachedata.base.chatLog + '?id=' + data.id + '&type=' + data.type
                , zIndex: layer.zIndex
                , success: function (layero) {
                    layer.setTop(layero);
                }
            });
        },
        getInformation: function (data) {
            layer.close(im.getInformation.index);
            var id = data.id || {};
            return im.getInformation.index = layer.open({
                type: 2
                , title: '会员资料'
                , shade: false
                , maxmin: false
                , area: ['400px', '670px']
                , skin: 'layui-box layui-layer-border'
                , resize: true
                , content: cachedata.base.information + '?id=' + id
                , zIndex: layer.zIndex
                , success: function (layero) {
                    layer.setTop(layero);
                }
            });
        },
        groupMembers: function (othis, e) {
            var othis = $(this);
            var icon = othis.find('.layui-icon'), hide = function () {
                icon.html('&#xe602;');
                $("#layui-layim-chat > ul:eq(1)").remove();
                $(".layui-layim-group-search").remove();
                othis.data('show', null);
            };
            if (othis.data('show')) {
                hide();
            } else {
                icon.html('&#xe603;');
                othis.data('show', true);
                var members = cachedata.base.members || {}, ul = $("#layui-layim-chat"), li = '', membersCache = {};
                var info = JSON.parse(decodeURIComponent(othis.parent().data('json')));
                members.data = $.extend(members.data, {
                    id: info.id
                });
                $.get(members, function (res) {
                    var resp = eval('(' + res + ')');
                    var html = '<ul class="layui-unselect layim-group-list groupMembers" data-groupidx="' + info.id + '" style="height: 510px; display: block;right:-200px;padding-top: 10px;">';
                    layui.each(resp.data.list, function (index, item) {
                        html += '<li  id="' + item.id + '" isfriend="' + item.friendship + '" manager="' + item.type + '" gagTime="' + item.gagTime + '"><img src="' + item.avatar + '">';
                        item.type == 1 ?
                            (html += '<span style="color:#e24242">' + item.username + '</span><i class="layui-icon" style="color:#e24242">&#xe612;</i>') :
                            (item.type == 2 ?
                                (html += '<span style="color:#de6039">' + item.username + '</span><i class="layui-icon" style="color:#eaa48e">&#xe612;</i>') :
                                (html += '<span>' + item.username + '</span>'));
                        html += '</li>';
                        membersCache[item.id] = item;
                    });
                    html += '</ul>';
                    html += '<div class="layui-layim-group-search" socket-event="groupSearch"><input placeholder="搜索"></div>';
                    ul.append(html);
                    im.contextMenu();
                });
            }
        },
        popMsg: function (data, msg) { //删除本地最新一条发送失败的消息
            var timestamp = '.timestamp' + data.mine.timestamp;
            $(timestamp).html('<i class="layui-icon" style="color: #F44336;font-size: 20px;float: left;margin-top: 1px;">&#x1007;</i>' + msg);
        },
        menuChat: function () {
            return data = {
                text: "发送消息",
                icon: "&#xe63a;",
                callback: function (ele) {
                    var othis = ele.parent(), type = othis.data('type'),
                        name = othis.data('name'), avatar = othis.data('img'),
                        id = othis.data('id');
                    layim.chat({
                        name: name
                        , type: type
                        , avatar: avatar
                        , id: id
                        , status: othis.data('status')
                        , sign: othis.data('sign')
                    });
                }
            }
        },
        menuInfo: function () {
            return data = {
                text: "查看资料",
                icon: "&#xe62a;",
                callback: function (ele) {
                    var othis = ele.parent(), type = othis.data('type'), id = othis.data('id');
                    im.getInformation({
                        id: id,
                        type: type
                    });
                }
            }
        },
        menuChatLog: function () {
            return data = {
                text: "聊天记录",
                icon: "&#xe60e;",
                callback: function (ele) {
                    var othis = ele.parent(), type = othis.data('type'), name = othis.data('name'),
                        id = othis.data('id');
                    im.getChatLog({
                        name: name,
                        id: id,
                        type: type
                    });
                }
            }
        },
        matchContent: function (content, msgType) {
            msgType = Number(msgType);
            switch (msgType) {
                case 2:
                    return content.replace(/img\[([^\s]+?)\]/g, function (img) {  //图片
                        return '[图片]';
                    });
                case 3:
                    return content.replace(/redpackage\[([^\s]+?)\]/g, function (redpackage) {  //红包
                        return '[红包]';
                    });
                case 4:
                    return content.replace(/voice\[([^\s]+?)\]/g, function (img) {  //语音
                        return '[语音]';
                    });
                case 5:
                    return '[游戏分享，请在手机app游玩]';
                default:
                    var returnMsg = '';
                    returnMsg = content.replace(/face\[([^\s\[\]]+?)\]/g, function (face) {  //表情
                        return '[表情]';
                    });
                    return returnMsg;
            }
        }
    };
    exports('socket', socket);
    exports('im', im);

    // 通知消息
    function getMessage() {
        $('.iframe_music').remove();
        $('body').append('<video controls="" class="iframe_music" autoplay="" name="media" style="display: none"><source src="/backend/resources/dist/music/music.mp3" type="audio/x-wav"></video>');
    };

    function turn_time(timestamp) {
        var date = new Date(timestamp * 1000);
        var year = date.getFullYear();
        var month = date.getMonth() + 1;
        var day = date.getDate();
        var hour = date.getHours();
        var minute = date.getMinutes();
        var second = date.getSeconds();
        return year + '-' + append_zero(month) + '-' + append_zero(day) + ' ' + append_zero(hour) + ':' + append_zero(minute) + ':' + append_zero(second);
    }

    // 补0操作
    function append_zero(num) {
        if (parseInt(num) < 10) {
            num = '0' + num;
        }
        return num;
    }
});