/**

 @Name：layim v3.9.1 Pro 商用版
 @Author：贤心
 @Site：http://layim.layui.com
 @License：LGPL

 */

layui.define(['layer', 'laytpl', 'upload'], function (exports) {

    var v = '3.9.1';
    var $ = layui.$;
    var layer = layui.layer;
    var laytpl = layui.laytpl;
    var device = layui.device();
    var atArr = [];
    var timer = 0;
    var atCache = {};
    var atStatus = false;
    var SHOW = 'layui-show', THIS = 'layim-this', MAX_ITEM = 20;

    //回调
    var call = {};

    //对外API
    var LAYIM = function () {
        this.v = v;
        $('body').on('click', '*[layim-event]', function (e) {
            var othis = $(this), methid = othis.attr('layim-event');
            events[methid] ? events[methid].call(this, othis, e) : '';
        });
    };

    //基础配置
    LAYIM.prototype.config = function (options) {
        var skin = [];
        layui.each(Array(5), function (index) {
            skin.push(layui.cache.dir + 'css/modules/layim/skin/' + (index + 1) + '.jpg')
        });
        options = options || {};
        options.skin = options.skin || [];
        layui.each(options.skin, function (index, item) {
            skin.unshift(item);
        });
        options.skin = skin;
        options = $.extend({
            isfriend: !0
            , isgroup: !0
            , voice: 'default.mp3'
        }, options);
        if (!window.JSON || !window.JSON.parse) return;
        init(options);
        return this;
    };

    //监听事件
    LAYIM.prototype.on = function (events, callback) {
        if (typeof callback === 'function') {
            call[events] ? call[events].push(callback) : call[events] = [callback];
        }
        return this;
    };

    //获取所有缓存数据
    LAYIM.prototype.cache = function () {
        return cache;
    };
    LAYIM.prototype.atArr = function () {
        return $.unique(atArr);
    };

    //打开一个自定义的会话界面
    LAYIM.prototype.chat = function (data) {
        if (!window.JSON || !window.JSON.parse) return;
        return popchat(data), this;
    };

    //设置聊天界面最小化
    LAYIM.prototype.setChatMin = function () {
        return setChatMin(), this;
    };

    //设置当前会话状态
    LAYIM.prototype.setChatStatus = function (str) {
        var thatChat = thisChat();
        if (!thatChat) return;
        var status = thatChat.elem.find('.layim-chat-status');
        return status.html(str), this;
    };

    //设置当前会话个性签名
    LAYIM.prototype.setSign = function (str) {
        var thatChat = thisChat();
        if (!thatChat) return;
        var sign = thatChat.elem.find('.layim-sign');
        return sign.html(str), this;
    };

    //接受消息
    LAYIM.prototype.getMessage = function (data) {
        return getMessage(data), this;
    };

    //桌面消息通知
    LAYIM.prototype.notice = function (data) {
        return notice(data), this;
    };

    //声音通知
    LAYIM.prototype.voice = function () {
        return voice();
    };

    //打开添加好友/群组面板
    LAYIM.prototype.add = function (data) {
        return popAdd(data), this;
    };

    //好友分组面板
    LAYIM.prototype.setFriendGroup = function (data) {
        return popAdd(data, 'setGroup'), this;
    };

    //消息盒子的提醒
    LAYIM.prototype.msgbox = function (nums) {
        return msgbox(nums), this;
    };

    //添加好友/群
    LAYIM.prototype.addList = function (data) {
        return addList(data), this;
    };

    //删除好友/群
    LAYIM.prototype.removeList = function (data) {
        return removeList(data), this;
    };

    //设置好友在线/离线状态
    LAYIM.prototype.setFriendStatus = function (id, type) {
        var list = $('.layim-friend' + id).find('img');
        list[type === 'online' ? 'removeClass' : 'addClass']('layim-list-gray');
        // type === 'online' ? sortSetOnline(id) : sortSetOffline(id);
        // 同步设置当前会话
        var thisChat = layui.layim.thisChat();
        if (thisChat) {
            if (thisChat.data.id === id) {
                var avatar = $(thisChat.elem).find('.layim-friend' + id);
                avatar[type === 'online' ? 'removeClass' : 'addClass']('layim-list-gray');
                type === 'online' ? layui.layim.setChatStatus('<span style="color:#FF5722;">在线</span>') : layui.layim.setChatStatus('<span style="color:#444;">离线</span>');
            }
        }
        // 更新缓存
        var _list = $('.layim-friend' + id + '[data-type="friend"]');
        var index = _list.data('index');
        if (_list.index() >= 0) {
            layui.layim.cache().friend[index].list[_list.index()].status = type;
        }
    };

    //解析聊天内容
    LAYIM.prototype.content = function (content, msgType) {
        return layui.data.content(content, msgType);
    };

    //储存消息到本地
    LAYIM.prototype.pushChatlog = function (message) {
        return pushChatlog(message);
    };

    //设置在线
    LAYIM.prototype.sortSetOnline = function (id) {
        return sortSetOnline(id);
    };


    //主模板
    var listTpl = function (options) {
        var nodata = {
            friend: "该分组下暂无好友"
            , group: "暂无群组"
            , history: "暂无历史会话"
        };

        options = options || {};
        options.item = options.item || ('d.' + options.type);

        return ['{{# var length = 0; layui.each(' + options.item + ', function(i, data){ length++; }}'
            , '<li layim-event="chat" data-type="' + options.type + '" data-index="{{ ' + (options.index || 'i') + ' }}" class="layim-' + (options.type === 'history' ? '{{i}}' : options.type + '{{data.id}}') + '"><img src="{{ data.avatar }}" class="{{ data.status === "offline" ? "layim-list-gray" : "" }}"><span><span class="label label-success label-level">{{data.level||""}}</span>{{ data.username||data.groupname||data.name||"佚名" }}</span><p>{{ data.remark||data.sign||"" }}</p><span class="layim-msg-status" style="{{data.unReadCount > 0 ? "display: block" : ""}}">{{data.unReadCount}}</span></li>'
            , '{{# }); if(length === 0){ }}'
            , '<li class="layim-null">' + (nodata[options.type] || "暂无数据") + '</li>'
            , '{{# } }}'].join('');
    };

    var elemTpl = ['<div class="layui-layim-main">'
        , '<div class="layui-layim-info">'
        // ,'<div class="layui-layim-user" style="cursor: pointer" layim-event="myInformation">{{ d.mine.username }}</div>'
        , '<div class="layui-layim-user" style="cursor: pointer">{{ d.mine.username }}</div>'
        , '<div class="layui-layim-status">'
        , '{{# if(d.mine.status === "online"){ }}'
        , '<span class="layui-icon layim-status-online" layim-event="status" lay-type="show">&#xe617;</span>'
        , '{{# } else if(d.mine.status === "hide") { }}'
        , '<span class="layui-icon layim-status-hide" layim-event="status" lay-type="show">&#xe60f;</span>'
        , '{{# } }}'
        , '<ul class="layui-anim layim-menu-box">'
        , '<li {{d.mine.status === "online" ? "class=layim-this" : ""}} layim-event="status" lay-type="online"><i class="layui-icon">&#xe605;</i><cite class="layui-icon layim-status-online">&#xe617;</cite>在线</li>'
        , '<li {{d.mine.status === "hide" ? "class=layim-this" : ""}} layim-event="status" lay-type="hide"><i class="layui-icon">&#xe605;</i><cite class="layui-icon layim-status-hide">&#xe60f;</cite>隐身</li>'
        , '</ul>'
        , '</div>'
        // ,'<input class="layui-layim-remark" placeholder="编辑签名" value="{{ d.mine.remark||d.mine.sign||"" }}">'
        , '</div>'
        , '<ul class="layui-unselect layui-layim-tab{{# if(!d.base.isfriend || !d.base.isgroup){ }}'
        , ' layim-tab-two'
        , '{{# } }}">'
        , '<li class="layui-icon'
        , '{{# if(!d.base.isfriend){ }}'
        , ' layim-hide'
        , '{{# } else { }}'
        , ' layim-this'
        , '{{# } }}'
        , '" title="联系人" layim-event="tab" lay-type="friend">&#xe612;</li>'
        , '<li class="layui-icon'
        , '{{# if(!d.base.isgroup){ }}'
        , ' layim-hide'
        , '{{# } else if(!d.base.isfriend) { }}'
        , ' layim-this'
        , '{{# } }}'
        // ,'" title="群组" layim-event="tab" lay-type="group">&#xe613;</li>'
        , '" title="群组" layim-event="tab" lay-type="group" style="display:none">&#xe613;</li>'
        , '<li class="layui-icon" title="历史会话" layim-event="tab" lay-type="history">&#xe611;</li>'
        , '</ul>'
        , '<ul class="layui-unselect layim-tab-content {{# if(d.base.isfriend){ }}layui-show{{# } }} layim-list-friend">'
        , '{{# layui.each(d.friend, function(index, item){ var spread = d.local["spread"+index]; }}'
        , '<li data-id="{{ item.id}}">'
        , '<h5 layim-event="spread" lay-type="{{ spread }}"><i class="layui-icon">{{# if(spread === "true"){ }}&#xe61a;{{# } else {  }}&#xe602;{{# } }}</i><span>{{ item.groupname||"未命名分组"+index }}</span><em>(<cite class="layim-count"> {{ (item.list||[]).length }}</cite>)</em></h5>'
        , '<ul class="layui-layim-list {{# if(spread === "true"){ }}'
        , ' layui-show'
        , '{{# } }}">'
        , listTpl({
            type: "friend"
            , item: "item.list"
            , index: "index"
        })
        , '</ul>'
        , '</li>'
        , '{{# }); if(d.friend.length === 0){ }}'
        , '<li><ul class="layui-layim-list layui-show"><li class="layim-null">暂无联系人</li></ul>'
        , '{{# } }}'
        , '</ul>'
        , '<ul class="layui-unselect layim-tab-content {{# if(!d.base.isfriend && d.base.isgroup){ }}layui-show{{# } }}">'
        , '<li>'
        , '<ul class="layui-layim-list layui-show layim-list-group">'
        , listTpl({
            type: 'group'
        })
        , '</ul>'
        , '</li>'
        , '</ul>'
        , '<ul class="layui-unselect layim-tab-content  {{# if(!d.base.isfriend && !d.base.isgroup){ }}layui-show{{# } }}">'
        , '<li>'
        , '<ul class="layui-layim-list layui-show layim-list-history">'
        , listTpl({
            type: 'history'
        })
        , '</ul>'
        , '</li>'
        , '</ul>'
        , '<ul class="layui-unselect layim-tab-content">'
        , '<li>'
        , '<ul class="layui-layim-list layui-show" id="layui-layim-search"></ul>'
        , '</li>'
        , '</ul>'
        , '<ul class="layui-unselect layui-layim-tool">'
        , '{{# if(layui.layim.cache().mine.id.match("manager")){ }}'
        , '<li class="layui-icon layim-tool-search" layim-event="search" title="搜索">&#xe615;</li>'
        , '{{# } }}'
        , '{{# if(d.base.msgbox){ }}'
        , '<li class="layui-icon layim-tool-msgbox" layim-event="msgbox" title="消息盒子">&#xe645;<span class="layui-anim"></span></li>'
        , '{{# } }}'
        , '{{# if(d.base.find){ }}'
        , '<li class="layui-icon layim-tool-find" layim-event="find" title="查找">&#xe608;</li>'
        , '{{# } }}'
        // ,'<li class="layui-icon layim-tool-menu" layim-event="menu" title="菜单" style="font-size: 20px">&#xe66b;</li>'
        //   ,'<li class="layui-icon layim-tool-plan" layim-event="planselect" title="在线计划" style="font-size: 20px">&#xe857;<span class="layui-badge-dot layui-hide" style="position: absolute;top:5px;"></span></li>'
        // ,'<li class="layui-icon layim-tool-rank_btn" layim-event="rank_btn" title="排行榜" style="font-size: 21px">&#xe63c;</li>'
        , '<li class="layui-icon layim-tool-skin" layim-event="skin" title="更换背景">&#xe61b;</li>'
        , '{{# if(!d.base.copyright){ }}'
        , '<li class="layui-icon layim-tool-about" layim-event="about" title="关于">&#xe60b;</li>'
        , '{{# } }}'
        , '</ul>'
        , '<div class="layui-layim-search"><input><label class="layui-icon" layim-event="memberSearch" style="margin-right: 25px">&#xe615;</label><label class="layui-icon" layim-event="closeSearch">&#x1007;</label></div>'
        , '</div>'].join('');

    //换肤模版
    var elemSkinTpl = ['<ul class="layui-layim-skin">'
        , '{{# layui.each(d.skin, function(index, item){ }}'
        , '<li><img layim-event="setSkin" src="{{ item }}"></li>'
        , '{{# }); }}'
        , '<li layim-event="setSkin"><cite>简约</cite></li>'
        , '</ul>'].join('');

    //聊天主模板
    var elemChatTpl = ['<div class="layim-chat layim-chat-{{d.data.type}}{{d.first ? " layui-show" : ""}}">'
        , '<div class="layui-unselect layim-chat-title">'
        , '<div class="layim-chat-other">'
        , '<img class="layim-{{ d.data.type }}{{ d.data.id }} {{ d.data.status === "offline" ? "layim-list-gray" : "" }}" src="{{ d.data.avatar }}" {{ d.data.type === "friend" ? \"style=\'cursor: pointer;\'\" : \"\" }} data-id="{{d.data.id}}" {{ d.data.type === "friend" ? \"layim-event=\'information\'\" : \"\"}}><span class="layim-chat-username" layim-event="{{ d.data.type==="group" ? \"groupMembers\" : \"\" }}">{{ d.data.name||"佚名" }} {{d.data.temporary ? "<cite>临时会话</cite>" : ""}}{{# if(d.data.type==="group"){ }} <em class="layim-chat-members"></em><i class="layui-icon">&#xe61a;</i> {{# } else { }} <span class="label label-level label-success">{{d.data.level || ""}}{{# }}}</span></span>'
        , '<p class="layim-chat-status"></p>'
        , '</div>'
        , '</div>'
        // ,'<div style="position:absolute;width:auto;left:110px;top:-35px;font-size:12px;height:32px;overflow-y:auto;color:#a2a2a2" class="layim-sign"></div>'
        , '<div class="layim-chat-main">'
        , '{{# if(d.data.type == "group") {}}<div class="layim-chat-top-msg" {{# if(!d.data.top_msg) {}}style="display:none" {{# }}}><cite style="display: block;color: #ff4c52">置顶消息'
        , '{{# if(layui.layim.cache().mine.id.match("manager")) {}}<a href="javascript:;" class="layui-icon" style="position: absolute;right: 0" layim-event="cancelTop">&#x1006;</a>{{# }}}</cite><span style="margin-left: 10px">{{layui.data.content(d.data.top_msg, d.data.top_msg_type)}}</span></div>{{# }}}'
        , '<ul></ul>'
        , '</div>'
        , '<div class="layim-chat-footer">'
        , '<div class="layui-unselect layim-chat-tool" data-json="{{encodeURIComponent(JSON.stringify(d.data))}}">'
        , '<span class="layui-icon layim-tool-face" title="选择表情" layim-event="face">&#xe60c;</span>'
        , '{{# if(d.base && d.base.uploadImage){ }}'
        , '<span class="layui-icon layim-tool-image" title="上传图片" layim-event="image">&#xe60d;<input type="file" name="file"></span>'
        , '{{# }; }}'
        , '{{# if(d.data && d.data.type === "group"){ }}'
        // ,'<span class="layui-icon layim-tool-redpackage" title="发红包" layim-event="redpackage"><img src="/resources/dist/img/redpackage.png" alt="发红包" width="24px"></span>'
        // ,'<span class="layui-icon layim-tool-video" title="开奖直播" layim-event="video" data-id="{{ d.data.id }}">&#xe638;</span>'
        // ,'<span class="layui-icon layim-tool-game" title="在线投注" layim-event="game" data-id="{{ d.data.id }}">&#xe7ae;</span>'
        , '{{# }; }}'
        , '{{# if(d.data && d.data.type === "group" && layui.layim.cache().mine.id.match("manager")){ }}'
        // ,'<span class="layui-icon layim-tool-notice" title="定时消息" layim-event="notice">&#xe645;</span>'
        // ,'<span class="layui-icon layim-tool-plan" title="实时计划" layim-event="box_plan" style="font-size: 20px">&#xe857;</span>'
        , '{{# }; }}'
        , '{{# if(d.base && d.base.uploadFile){ }}'
        , '<span class="layui-icon layim-tool-image" title="发送文件" layim-event="image" data-type="file">&#xe61d;<input type="file" name="file"></span>'
        , '{{# }; }}'
        , '{{# if(d.base && d.base.isAudio){ }}'
        , '<span class="layui-icon layim-tool-audio" title="发送网络音频" layim-event="media" data-type="audio">&#xe6fc;</span>'
        , '{{# }; }}'
        , '{{# if(d.base && d.base.isVideo){ }}'
        , '<span class="layui-icon layim-tool-video" title="发送网络视频" layim-event="media" data-type="video">&#xe6ed;</span>'
        , '{{# }; }}'
        , '{{# layui.each(d.base.tool, function(index, item){ }}'
        , '<span class="layui-icon layim-tool-{{item.alias}}" title="{{item.title}}" layim-event="extend" lay-filter="{{ item.alias }}">{{item.icon}}</span>'
        , '{{# }); }}'
        , '{{# if(d.base && d.base.chatLog){ }}'
        , '<span class="layim-tool-log" layim-event="chatLog"><i class="layui-icon">&#xe60e;</i>聊天记录</span>'
        , '{{# }; }}'
        , '</div>'
        , '<div class="layim-chat-textarea"><textarea></textarea></div>'
        , '<div class="layim-chat-bottom">'
        , '<div class="layim-chat-send">'
        , '{{# if(!d.base.brief){ }}'
        , '<span class="layim-send-close" layim-event="closeThisChat">关闭</span>'
        , '{{# } }}'
        , '<span class="layim-send-btn" layim-event="send">发送</span>'
        , '<span class="layim-send-set" layim-event="setSend" lay-type="show"><em class="layui-edge"></em></span>'
        , '<ul class="layui-anim layim-menu-box">'
        , '<li {{d.local.sendHotKey !== "Ctrl+Enter" ? "class=layim-this" : ""}} layim-event="setSend" lay-type="Enter"><i class="layui-icon">&#xe605;</i>按Enter键发送消息</li>'
        , '<li {{d.local.sendHotKey === "Ctrl+Enter" ? "class=layim-this" : ""}} layim-event="setSend"  lay-type="Ctrl+Enter"><i class="layui-icon">&#xe605;</i>按Ctrl+Enter键发送消息</li>'
        , '</ul>'
        , '</div>'
        , '</div>'
        , '</div>'
        , '</div>'].join('');

    //添加好友群组模版
    var elemAddTpl = ['<div class="layim-add-box">'
        , '<div class="layim-add-img"><img class="layui-circle" src="{{ d.data.avatar }}"><p>{{ d.data.name||"" }}</p></div>'
        , '<div class="layim-add-remark">'
        , '{{# if(d.data.type === "friend" && d.type === "setGroup"){ }}'
        , '<p>选择分组</p>'
        , '{{# } if(d.data.type === "friend"){ }}'
        , '<select class="layui-select" id="LAY_layimGroup">'
        , '{{# layui.each(d.data.group, function(index, item){ }}'
        , '<option value="{{ item.id }}">{{ item.groupname }}</option>'
        , '{{# }); }}'
        , '</select>'
        , '{{# } }}'
        , '{{# if(d.data.type === "group"){ }}'
        , '<p>请输入验证信息</p>'
        , '{{# } if(d.type !== "setGroup"){ }}'
        , '<textarea id="LAY_layimRemark" placeholder="验证信息" class="layui-textarea"></textarea>'
        , '{{# } }}'
        , '</div>'
        , '</div>'].join('');

    //聊天内容列表模版
    var elemChatMain = ['<li {{ d.mine ? "class=layim-chat-mine" : "data-nickname=" + d.nickname }} {{# if(d.cid){ }}data-cid="{{d.cid}}"  {{# } }}>'
        , '<div class="layim-chat-user"><img src="{{ d.avatar }}" {{# if(d.type === \'group\' && layui.layim.cache().mine.id.match("manager") && d.fromid && d.fromid.match("member")){ }} style="cursor:pointer;" layim-event="information" data-id="{{d.fromid}}" class="layim-friend{{ d.fromid}}"{{# }}} ><cite>'
        , '{{# if(d.mine){ }}'
        // ,'<i>{{# if (d.level){ }}<span class="layui-badge">{{d.level }}</span>{{# }}}{{# if(d.type === "group" && layui.layim.cache().mine.id.match("manager")) {}}<i class="layui-icon" layim-event="setTop" style="padding-right: 5px;cursor: pointer;">&#xe66e;</i>{{# }}}{{ layui.data.date(d.timestamp) }}</i><span>{{ d.username||"佚名" }}</span>{{# if(d.msgType !== 3) {}}<i class="layui-icon" layim-event="revokeMessage" style="padding: 0;cursor: pointer;">&#xe669;</i>{{# } }}'
        , '<i>{{# if (d.level){ }}<span class="layui-badge">{{d.level }}</span>{{# }}}{{# if(d.type === "group" && layui.layim.cache().mine.id.match("manager")) {}}{{# }}}{{ layui.data.date(d.timestamp) }}</i><span>{{ d.username||"佚名" }}</span>{{# if(d.msgType !== 3) {}}{{# } }}'
        , '{{# } else { }}'
        // ,'<span>{{ d.username||"佚名" }}</span><i>{{ layui.data.date(d.timestamp) }}{{# if (d.level){ }}<span class="layui-badge">{{d.level }}</span>{{# }}}</i>{{# if(d.type === "group" && layui.layim.cache().mine.id.match("manager")) {}}<i class="layui-icon" layim-event="setTop" style="padding-right: 5px;cursor: pointer;">&#xe66e;</i>{{# }}}'
        , '<span>{{ d.username||"佚名" }}</span><i>{{ layui.data.date(d.timestamp) }}{{# if (d.level){ }}<span class="layui-badge">{{d.level }}</span>{{# }}}</i>{{# if(d.type === "group" && layui.layim.cache().mine.id.match("manager")) {}}{{# }}}'
        , '{{# } }}'
        , '</cite></div>'
        , '<div class="layim-chat-text timestamp{{ d.timestamp }}">{{ layui.data.content(d.content||"&nbsp", d.msgType) }}</div>'
        , '</li>'].join('');

    var elemChatList = '<li class="layim-{{ d.data.type }}{{ d.data.id }} layim-chatlist-{{ d.data.type }}{{ d.data.id }} layim-this" layim-event="tabChat"><img src="{{ d.data.avatar }}"><span class="layim-msg-status">0</span><span>{{ d.data.name||"佚名" }}</span>{{# if(!d.base.brief){ }}<i class="layui-icon" layim-event="closeChat">&#x1007;</i>{{# } }}</li>';

    //补齐数位
    var digit = function (num) {
        return num < 10 ? '0' + (num | 0) : num;
    };

    //转换时间
    layui.data.date = function (timestamp) {
        var d = new Date(timestamp || new Date());
        return d.getFullYear() + '-' + digit(d.getMonth() + 1) + '-' + digit(d.getDate())
            + ' ' + digit(d.getHours()) + ':' + digit(d.getMinutes()) + ':' + digit(d.getSeconds());
    };

    //转换内容
    layui.data.content = function (content, msgType) {
        msgType = Number(msgType);
        //支持的html标签
        var html = function (end) {
            return new RegExp('\\n*\\[' + (end || '') + '(code|pre|div|span|p|table|thead|th|tbody|tr|td|ul|li|ol|li|dl|dt|dd|h2|h3|h4|h5)([\\s\\S]*?)\\]\\n*', 'g');
        };
        content = (content || '').replace(/&(?!#?[a-zA-Z0-9]+;)/g, '&amp;')
            .replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/'/g, '&#39;').replace(/"/g, '&quot;') //XSS
            .replace(/@(\S+)(\s+?|$)/g, '@<a href="javascript:;">$1</a>$2') //转义@

            .replace(/face\[([^\s\[\]]+?)\]/g, function (face) {  //转义表情
                var alt = face.replace(/^face/g, '');
                return '<img alt="' + alt + '" title="' + alt + '" src="' + faces[alt] + '">';
            })
            // .replace(/img\[([^\s]+?)\]/g, function(img){  //转义图片
            //   return '<img class="layui-layim-photos" src="' + img.replace(/(^img\[)|(\]$)/g, '') + '">';
            // })
            // .replace(/file\([\s\S]+?\)\[[\s\S]*?\]/g, function(str){ //转义文件
            //   var href = (str.match(/file\(([\s\S]+?)\)\[/)||[])[1];
            //   var text = (str.match(/\)\[([\s\S]*?)\]/)||[])[1];
            //   if(!href) return str;
            //   return '<a class="layui-layim-file" href="'+ href +'" download target="_blank"><i class="layui-icon">&#xe61e;</i><cite>'+ (text||href) +'</cite></a>';
            // })
            // .replace(/audio\[([^\s]+?)\]/g, function(audio){  //转义音频
            //   return '<div class="layui-unselect layui-layim-audio" layim-event="playAudio" data-src="' + audio.replace(/(^audio\[)|(\]$)/g, '') + '"><i class="layui-icon">&#xe652;</i><p>音频消息</p></div>';
            // })
            // .replace(/video\[([^\s]+?)\]/g, function(video){  //转义音频
            //   return '<div class="layui-unselect layui-layim-video" layim-event="playVideo" data-src="' + video.replace(/(^video\[)|(\]$)/g, '') + '"><i class="layui-icon">&#xe652;</i></div>';
            // })

            .replace(/a\([\s\S]+?\)\[[\s\S]*?\]/g, function (str) { //转义链接
                var href = (str.match(/a\(([\s\S]+?)\)\[/) || [])[1];
                var text = (str.match(/\)\[([\s\S]*?)\]/) || [])[1];
                if (!href) return str;
                return '<a href="' + href + '" target="_blank">' + (text || href) + '</a>';
            }).replace(html(), '\<$1 $2\>').replace(html('/'), '\</$1\>') //转移HTML代码
            .replace(/\n/g, '<br>') //转义换行
        switch (msgType) {
            case 2:
                content = content.replace(/img\[([^\s]+?)\]/g, function (img) {  //转义图片
                    return '<img class="layui-layim-photos" src="' + img.replace(/(^img\[)|(\]$)/g, '') + '">';
                });
                break;
            case 3:
                var info = content.replace(/(^redpackage\[)|(\]$)/g, '').split('_');
                info[1] = info[1] == 0 || info[1] == undefined ? '' : info[1];
                info[2] = info[2] == 0 || info[2] == undefined ? '' : info[2];
                var data_type = 2;
                if (info[3] == 0 || info[3] == undefined) {
                    info[3] = '';
                    data_type = 1;
                }
                content = '<div class="redpack" id="' + info[0] + '" data-type="' + data_type + '"><div class="topcontent"><p class="topcontent-content">' + info[1] + '</p><p class="topcontent-content2">' + info[2] + '</p><p>' + info[3] + '</p></div><div id="redpack-open"><span class="redpack-font">開</span></div></div>';
                break;
            case 5:
                content = '[游戏分享，请在手机app游玩]';
                break;
            default:

        }
        return content;
    };

    //Ajax
    var post = function (options, callback, tips) {
        options = options || {};
        return $.ajax({
            url: options.url
            , type: options.type || 'get'
            , data: options.data
            , dataType: options.dataType || 'json'
            , cache: false
            , success: function (res) {
                res.code == 0
                    ? callback && callback(res.data || {})
                    : layer.msg(res.msg || ((tips || 'Error') + ': LAYIM_NOT_GET_DATA', {
                        zIndex: layer.zIndex,
                        success: function (layero) {
                            layer.setTop(layero);
                        }
                    }), {
                        time: 5000
                    });
            }, error: function (err, msg) {
                window.console && console.log && console.error('LAYIM_DATE_ERROR：' + msg, {
                    zIndex: layer.zIndex,
                    success: function (layero) {
                        layer.setTop(layero);
                    }
                });
            }
        });
    };

    //处理初始化信息
    var cache = {message: {}, chat: []}, init = function (options) {
        var init = options.init || {}
        mine = init.mine || {}
            , local = layui.data('layim')[mine.id] || {}
            , obj = {
            base: options
            , local: local
            , mine: mine
            , history: local.history || {}
        }, create = function (data) {
            var mine = data.mine || {};
            var local = layui.data('layim')[mine.id] || {}, obj = {
                base: options //基础配置信息
                , local: local //本地数据
                , mine: mine //我的用户信息
                , friend: data.friend || [] //联系人信息
                , group: data.group || [] //群组信息
                , history: local.history || {} //历史会话信息
            };
            cache = $.extend(cache, obj);
            popim(laytpl(elemTpl).render(obj));
            if (local.close || options.min) {
                popmin();
            }
            layui.each(call.ready, function (index, item) {
                item && item(obj);
            });
        };
        cache = $.extend(cache, obj);
        if (options.brief) {
            return layui.each(call.ready, function (index, item) {
                item && item(obj);
            });
        }
        ;
        init.url ? post(init, create, 'INIT') : create(init);
        events.listenAt();
    };

    //显示主面板
    var layimMain, popim = function (content) {
        return layer.open({
            type: 1
            , area: ['260px', '520px']
            , skin: 'layui-box layui-layim'
            , title: '&#8203;'
            , offset: 'rb'
            , id: 'layui-layim'
            , shade: false
            , anim: 2
            , resize: false
            , content: content
            , zIndex: layer.zIndex
            , success: function (layero) {
                layer.setTop(layero);
                layimMain = layero;

                setSkin(layero);

                if (cache.base.right) {
                    layero.css('margin-left', '-' + cache.base.right);
                }
                if (layimClose) {
                    layer.close(layimClose.attr('times'));
                }

                //按最新会话重新排列
                var arr = [], historyElem = layero.find('.layim-list-history');
                historyElem.find('li').each(function () {
                    arr.push($(this).prop('outerHTML'))
                });
                if (arr.length > 0) {
                    arr.reverse();
                    historyElem.html(arr.join(''));
                }

                banRightMenu();
                events.sign();
            }
            , cancel: function (index) {
                popmin();
                var local = layui.data('layim')[cache.mine.id] || {};
                local.close = true;
                layui.data('layim', {
                    key: cache.mine.id
                    , value: local
                });
                return false;
            }
        });
    };

    //屏蔽主面板右键菜单
    var banRightMenu = function () {
        layimMain.on('contextmenu', function (event) {
            event.cancelBubble = true
            event.returnValue = false;
            return false;
        });

        var hide = function () {
            layer.closeAll('tips');
        };

        //自定义历史会话右键菜单
        layimMain.find('.layim-list-history').on('contextmenu', 'li', function (e) {
            var othis = $(this);
            var html = '<ul data-id="' + othis[0].id + '" data-index="' + othis.data('index') + '"><li layim-event="menuHistory" data-type="one">移除该会话</li><li layim-event="menuHistory" data-type="all">清空全部会话列表</li></ul>';

            if (othis.hasClass('layim-null')) return;

            layer.tips(html, this, {
                tips: 1
                , time: 0
                , anim: 5
                , fixed: true
                , skin: 'layui-box layui-layim-contextmenu'
                , zIndex: layer.zIndex
                , success: function (layero) {
                    layer.setTop(layero);
                    var stopmp = function (e) {
                        stope(e);
                    };
                    layero.off('mousedown', stopmp).on('mousedown', stopmp);
                }
            });
            $(document).off('mousedown', hide).on('mousedown', hide);
            $(window).off('resize', hide).on('resize', hide);

        });
    }

    //主面板最小化状态
    var layimClose, popmin = function (content) {
        if (layimClose) {
            layer.close(layimClose.attr('times'));
        }
        if (layimMain) {
            layimMain.hide();
        }
        cache.mine = cache.mine || {};
        return layer.open({
            type: 1
            ,
            title: false
            ,
            id: 'layui-layim-close'
            ,
            skin: 'layui-box layui-layim-min layui-layim-close'
            ,
            shade: false
            ,
            closeBtn: false
            ,
            anim: 2
            ,
            offset: 'rb'
            ,
            resize: false
            ,
            zIndex: layer.zIndex
            ,
            content: '<img src="' + (cache.mine.avatar || (layui.cache.dir + 'css/pc/layim/skin/logo.jpg')) + '"><span>' + (content || cache.base.title || '我的LayIM') + '</span>'
            ,
            move: '#layui-layim-close img'
            ,
            success: function (layero, index) {
                layer.setTop(layero);
                layimClose = layero;
                if (cache.base.right) {
                    layero.css('margin-left', '-' + cache.base.right);
                }
                layero.on('click', function () {
                    layer.close(index);
                    layimMain.show();
                    var local = layui.data('layim')[cache.mine.id] || {};
                    delete local.close;
                    layui.data('layim', {
                        key: cache.mine.id
                        , value: local
                    });
                });
            }
        });
    };

    //显示聊天面板
    var layimChat, layimMin, chatIndex, To = {}, popchat = function (data) {
        data = data || {};

        var chat = $('#layui-layim-chat'), render = {
            data: data
            , base: cache.base
            , local: cache.local
        };

        if (!data.id) {
            return layer.msg('非法用户', {
                zIndex: layer.zIndex,
                success: function (layero) {
                    layer.setTop(layero);
                }
            });
        }

        if (chat[0]) {
            var list = layimChat.find('.layim-chat-list');
            var listThat = list.find('.layim-chatlist-' + data.type + data.id);
            var hasFull = layimChat.find('.layui-layer-max').hasClass('layui-layer-maxmin');
            var chatBox = chat.children('.layim-chat-box');

            //如果是最小化，则还原窗口
            if (layimChat.css('display') === 'none') {
                layimChat.show();
            }

            if (layimMin) {
                layer.close(layimMin.attr('times'));
            }

            //如果出现多个聊天面板
            if (list.find('li').length === 1 && !listThat[0]) {
                hasFull || layimChat.css('width', 800);
                list.css({
                    height: layimChat.height()
                }).show();
                chatBox.css('margin-left', '200px');
            }

            //打开的是非当前聊天面板，则新增面板
            if (!listThat[0]) {
                list.append(laytpl(elemChatList).render(render));
                chatBox.append(laytpl(elemChatTpl).render(render));
                syncGray(data);
                resizeChat();
            }

            changeChat(list.find('.layim-chatlist-' + data.type + data.id));
            listThat[0] || viewChatlog();
            setHistory(data);
            hotkeySend();
            events.pasteImg();
            headerMenu(thisChat());
            return chatIndex;
        }

        render.first = !0;

        var index = chatIndex = layer.open({
            type: 1
            ,
            area: '600px'
            ,
            skin: 'layui-box layui-layim-chat'
            ,
            id: 'layui-layim-chat'
            ,
            title: '&#8203;'
            ,
            shade: false
            ,
            maxmin: true
            ,
            offset: data.offset || 'auto'
            ,
            anim: data.anim || 0
            ,
            closeBtn: cache.base.brief ? false : 1
            ,
            content: laytpl('<ul class="layui-unselect layim-chat-list">' + elemChatList + '</ul><div class="layim-chat-box">' + elemChatTpl + '</div><div class="layim-plan-list" style="height: 520px; display: none;right:-300px;"></div>').render(render)
            ,
            zIndex: layer.zIndex
            ,
            success: function (layero) {
                layimChat = layero;
                layer.setTop(layero);
                layero.css({
                    'min-width': '500px'
                    , 'min-height': '420px'
                });

                syncGray(data);

                typeof data.success === 'function' && data.success(layero);

                hotkeySend();
                setSkin(layero);
                setHistory(data);

                viewChatlog();
                showOffMessage();

                //聊天窗口的切换监听
                layui.each(call.chatChange, function (index, item) {
                    item && item(thisChat());
                });

                //查看大图
                layero.on('dblclick', '.layui-layim-photos', function () {
                    var src = this.src;
                    layer.close(popchat.photosIndex);
                    layer.photos({
                        photos: {
                            data: [{
                                "alt": "大图模式",
                                "src": src
                            }]
                        }
                        , shade: 0.01
                        , closeBtn: 2
                        , anim: 0
                        , resize: false
                        , zIndex: layer.zIndex
                        , success: function (layero, index) {
                            popchat.photosIndex = index;
                            layer.setTop(layero);
                        }
                    });
                });
            }
            ,
            full: function (layero) {
                layer.style(index, {
                    width: '100%'
                    , height: '100%'
                }, true);
                resizeChat();
            }
            ,
            resizing: resizeChat
            ,
            restore: resizeChat
            ,
            min: function () {
                setChatMin();
                return false;
            }
            ,
            end: function () {
                layer.closeAll('tips');
                layimChat = null;
            }
        });
        events.pasteImg();
        headerMenu(thisChat());
        return index;
    };

    //同步置灰状态
    var syncGray = function (data) {
        $('.layim-' + data.type + data.id).each(function () {
            if ($(this).find('img').hasClass('layim-list-gray')) {
                layui.layim.setFriendStatus(data.id, 'offline');
            }
        });
    };

    //重置聊天窗口大小
    var resizeChat = function () {
        var list = layimChat.find('.layim-chat-list')
            , chatMain = layimChat.find('.layim-chat-main')
            , chatHeight = layimChat.height()
            , plan = $('.layim-plan-list');
        list.css({
            height: chatHeight
        });
        chatMain.css({
            height: chatHeight - 20 - 80 - 158
        });
        plan.css({
            height: chatHeight
        });
    };

    //设置聊天窗口最小化 & 新消息提醒
    var setChatMin = function (newMsg) {
        var thatChat = newMsg || thisChat().data, base = layui.layim.cache().base;
        if (layimChat && !newMsg) {
            layimChat.hide();
        }
        layer.close(setChatMin.index);
        setChatMin.index = layer.open({
            type: 1
            , title: false
            , skin: 'layui-box layui-layim-min'
            , shade: false
            , closeBtn: false
            , anim: thatChat.anim || 2
            , offset: 'b'
            , move: '#layui-layim-min'
            , resize: false
            , area: ['182px', '50px']
            , zIndex: layer.zIndex
            , content: '<img id="layui-layim-min" src="' + thatChat.avatar + '"><span>' + thatChat.name + '</span>'
            , success: function (layero, index) {
                if (!newMsg) layimMin = layero;
                layer.setTop(layero);
                if (base.minRight) {
                    layer.style(index, {
                        left: $(window).width() - layero.outerWidth() - parseFloat(base.minRight)
                    });
                }

                layero.find('.layui-layer-content span').on('click', function () {
                    layer.close(index);
                    newMsg ? layui.each(cache.chat, function (i, item) {
                        popchat(item);
                    }) : layimChat.show();
                    if (newMsg) {
                        cache.chat = [];
                        chatListMore(false);
                    }
                });
                layero.find('.layui-layer-content img').on('click', function (e) {
                    stope(e);
                });
            }
        });
    };

    //打开添加好友、群组面板、好友分组面板
    var popAdd = function (data, type) {
        data = data || {};
        layer.close(popAdd.index);
        return popAdd.index = layer.open({
            type: 1
            , area: '430px'
            , title: {
                friend: '添加好友'
                , group: '加入群组'
            }[data.type] || ''
            , shade: false
            , resize: false
            , btn: type ? ['确认', '取消'] : ['发送申请', '关闭']
            , content: laytpl(elemAddTpl).render({
                data: {
                    name: data.username || data.groupname
                    , avatar: data.avatar
                    , group: data.group || parent.layui.layim.cache().friend || []
                    , type: data.type
                }
                , type: type
            })
            , yes: function (index, layero) {
                var groupElem = layero.find('#LAY_layimGroup')
                    , remarkElem = layero.find('#LAY_layimRemark')
                if (type) {
                    data.submit && data.submit(groupElem.val(), index);
                } else {
                    data.submit && data.submit(groupElem.val(), remarkElem.val(), index);
                }
            }
        });
    };

    //切换聊天
    var changeChat = function (elem, del) {
        layer.closeAll('tips');
        elem = elem || $('.layim-chat-list .' + THIS);
        var index = elem.index() === -1 ? 0 : elem.index();
        var str = '.layim-chat', cont = layimChat.find(str).eq(index);
        var hasFull = layimChat.find('.layui-layer-max').hasClass('layui-layer-maxmin');

        if (del) {

            //如果关闭的是当前聊天，则切换聊天焦点
            if (elem.hasClass(THIS)) {
                changeChat(index === 0 ? elem.next() : elem.prev());
            }

            var length = layimChat.find(str).length;

            //关闭聊天界面
            if (length === 1) {
                return layer.close(chatIndex);
            }

            elem.remove();
            cont.remove();

            //只剩下1个列表，隐藏左侧区块
            if (length === 2) {
                layimChat.find('.layim-chat-list').hide();
                if (!hasFull) {
                    layimChat.css('width', '600px');
                }
                layimChat.find('.layim-chat-box').css('margin-left', 0);
            }

            return false;
        }

        elem.addClass(THIS).siblings().removeClass(THIS);
        cont.addClass(SHOW).siblings(str).removeClass(SHOW);
        cont.find('textarea').focus();

        //聊天窗口的切换监听
        layui.each(call.chatChange, function (index, item) {
            if (layimChat.find(str).length > 1) {
                item && item(thisChat());
            }
        });
        showOffMessage();
        chatListMore(false);
    };

    //展示存在队列中的消息
    var showOffMessage = function () {
        var thatChat = thisChat();
        var message = cache.message[thatChat.data.type + thatChat.data.id];
        if (message) {
            //展现后，删除队列中消息
            delete cache.message[thatChat.data.type + thatChat.data.id];
        }
    };

    //头像右键菜单
    var headerMenu = function (thisChat) {
        if (thisChat.data.type == 'friend') return;
        thisChat.elem.on('contextmenu', '.layim-chat-main ul li:not(.layim-chat-mine) .layim-chat-user img', function (event) {
            event.cancelBubble = true;
            event.returnValue = false;
            return false;
        });
        var hide = function () {
            layer.closeAll('tips');
        };
        thisChat.elem.on('contextmenu', '.layim-chat-main ul li:not(.layim-chat-mine) .layim-chat-user img', function (e) {
            var othis = $(this);
            var li = othis.parent().parent();
            var html = '<ul data-id="' + othis.data('id') + '" data-nickname="' + li.data('nickname') + '" data-cid="' + li.data('cid') + '"><li layim-event="atOne">@ TA</li>';
            if (cache.mine.id.match('manager')) {
                html += '<li layim-event="shieldMsg">屏蔽该消息</li>'
            }
            html += '</ul>';
            layer.tips(html, this, {
                tips: 1
                , time: 0
                , anim: 5
                , fixed: true
                , skin: 'layui-box layui-layim-contextmenu layim-header-menu'
                , zIndex: layer.zIndex
                , success: function (layero) {
                    layer.setTop(layero);
                    var stopmp = function (e) {
                        stope(e);
                    };
                    layero.off('mousedown', stopmp).on('mousedown', stopmp);
                }
            });
            $(document).off('mousedown', hide).on('mousedown', hide);
            $(window).off('resize', hide).on('resize', hide);

        });
    };

    //获取当前聊天面板
    var thisChat = LAYIM.prototype.thisChat = function () {
        if (!layimChat) return;
        var index = $('.layim-chat-list .' + THIS).index();
        var cont = layimChat.find('.layim-chat').eq(index);
        var to = JSON.parse(decodeURIComponent(cont.find('.layim-chat-tool').data('json')));
        return {
            elem: cont
            , data: to
            , textarea: cont.find('textarea')
        };
    };

    //记录初始背景
    var setSkin = function (layero) {
        var local = layui.data('layim')[cache.mine.id] || {}
            , skin = local.skin;
        layero.css({
            'background-image': skin ? 'url(' + skin + ')' : function () {
                return cache.base.initSkin
                    ? 'url(' + (layui.cache.dir + 'css/modules/layim/skin/' + cache.base.initSkin) + ')'
                    : 'none';
            }()
        });
    };

    //记录历史会话
    var setHistory = function (data) {
        var local = layui.data('layim')[cache.mine.id] || {};
        var obj = {}, history = local.history || {};
        var is = history[data.type + data.id];

        if (!layimMain) return;

        var historyElem = layimMain.find('.layim-list-history');

        data.historyTime = new Date().getTime();
        history[data.type + data.id] = data;

        local.history = history;

        layui.data('layim', {
            key: cache.mine.id
            , value: local
        });

        if (is) return;

        obj[data.type + data.id] = data;

        var historyList = laytpl(listTpl({
            type: 'history'
            , item: 'd.data'
        })).render({data: obj});
        historyElem.prepend(historyList);
        historyElem.find('.layim-null').remove();
    };

    //发送消息
    var sendMessage = function (msgType, content) {
        var thatChat = thisChat(), ul = thatChat.elem.find('.layim-chat-main ul');
        var data = {
            username: cache.mine ? cache.mine.username : '访客'
            , avatar: cache.mine ? cache.mine.avatar : (layui.cache.dir + 'css/pc/layim/skin/logo.jpg')
            , id: cache.mine ? cache.mine.id : null
            , mine: true
            , timestamp: (new Date).getTime()
            , level: cache.mine ? cache.mine.level : ''
            , msgType: msgType ? msgType : 1
            , type: thatChat.data.type
        };
        var maxLength = cache.base.maxLength || 3000;
        data.content = content ? content : thatChat.textarea.val();
        if (data.content.replace(/\s/g, '') !== '') {

            if (data.content.length > maxLength) {
                return layer.msg('内容最长不能超过' + maxLength + '个字符', {
                    zIndex: layer.zIndex,
                    success: function (layero) {
                        layer.setTop(layero);
                    }
                })
            }

            ul.append(laytpl(elemChatMain).render(data));

            var param = {
                mine: data
                , to: thatChat.data
            }, message = {
                username: param.mine.username
                , avatar: param.mine.avatar
                , id: param.to.id
                , type: param.to.type
                , content: param.mine.content
                , timestamp: new Date().getTime()
                , mine: true
                , level: cache.mine ? cache.mine.level : ''
                , msgType: msgType ? msgType : 1
            };

            layui.each(call.sendMessage, function (index, item) {
                item && item(param, message);
            });
        }
        chatListMore(false);
        thatChat.textarea.val('').focus();
        atArr = [];
        layer.closeAll('tips');
        atStatus = false;
    };

    //桌面消息提醒
    var notice = function (data) {
        data = data || {};
        if (window.Notification) {
            if (Notification.permission === 'granted') {
                var notification = new Notification(data.title || '', {
                    body: data.content || ''
                    , icon: data.avatar || 'http://tp2.sinaimg.cn/5488749285/50/5719808192/1'
                });
            } else {
                Notification.requestPermission();
            }
            ;
        }
    };

    //消息声音提醒
    var voice = function () {
        if (device.ie && device.ie < 9) return;
        var audio = document.createElement("audio");
        audio.src = layui.cache.dir + 'css/modules/layim/voice/' + cache.base.voice;
        audio.play();
    };

    //接受消息
    var messageNew = {}, getMessage = function (data) {
        data = data || {};

        var elem = $('.layim-chatlist-' + data.type + data.id);
        var group = {}, index = elem.index();

        data.timestamp = data.timestamp || new Date().getTime();
        if (data.fromid == cache.mine.id) {
            data.mine = true;
        }
        data.system || pushChatlog(data);
        messageNew = JSON.parse(JSON.stringify(data));

        if (cache.base.voice && data.mine !== true) {
            voice();
        }

        if ((!layimChat && data.content) || index === -1) {
            if (cache.message[data.type + data.id]) {
                cache.message[data.type + data.id].push(data)
            } else {
                cache.message[data.type + data.id] = [data];

                //记录聊天面板队列
                if (data.type === 'friend') {
                    var friend;
                    layui.each(cache.friend, function (index1, item1) {
                        layui.each(item1.list, function (index, item) {
                            if (item.id == data.id) {
                                item.type = 'friend';
                                item.name = item.username;
                                cache.chat.push(item);
                                return friend = true;
                            }
                        });
                        if (friend) return true;
                    });
                    if (!friend) {
                        data.name = data.username;
                        data.temporary = true; //临时会话
                        cache.chat.push(data);
                    }
                } else if (data.type === 'group') {
                    var isgroup;
                    layui.each(cache.group, function (index, item) {
                        if (item.id == data.id) {
                            item.type = 'group';
                            item.name = item.groupname;
                            cache.chat.push(item);
                            return isgroup = true;
                        }
                    });
                    if (!isgroup) {
                        data.name = data.groupname;
                        cache.chat.push(data);
                    }
                } else {
                    data.name = data.name || data.username || data.groupname;
                    cache.chat.push(data);
                }
            }
            if (data.type === 'group') {
                layui.each(cache.group, function (index, item) {
                    if (item.id == data.id) {
                        group.avatar = item.avatar;
                        return true;
                    }
                });
            }
            if (!data.system) {
                if (cache.base.notice) {
                    notice({
                        title: '来自 ' + data.username + ' 的消息'
                        , content: data.content
                        , avatar: group.avatar || data.avatar
                    });
                }
                return setChatMin({
                    name: '收到新消息'
                    , avatar: group.avatar || data.avatar
                    , anim: 6
                });
            }
        }

        if (!layimChat) return;

        //接受到的消息不在当前Tab
        var thatChat = thisChat(), chatMain = thatChat.elem.find('.layim-chat-main');
        if (thatChat.data.type + thatChat.data.id !== data.type + data.id) {
            elem.addClass('layui-anim layer-anim-06');
            setTimeout(function () {
                elem.removeClass('layui-anim layer-anim-06')
            }, 300);
        }

        var cont = layimChat.find('.layim-chat').eq(index);
        var ul = cont.find('.layim-chat-main ul');

        //系统消息
        if (data.system) {
            if (index !== -1) {
                ul.append('<li class="layim-chat-system"><span>' + data.content + '</span></li>');
            }
        } else if (data.content.replace(/\s/g, '') !== '') {
            ul.append(laytpl(elemChatMain).render(data));
        }

        chatListMore(true);
    };

    //消息盒子的提醒
    var ANIM_MSG = 'layui-anim-loop layer-anim-05', msgbox = function (num) {
        var msgboxElem = layimMain.find('.layim-tool-msgbox');
        msgboxElem.find('span').addClass(ANIM_MSG).html(num);
    };

    //存储最近MAX_ITEM条聊天记录到本地
    var pushChatlog = function (message) {
        var local = layui.data('layim')[cache.mine.id] || {};
        local.chatlog = local.chatlog || {};
        var thisChatlog = local.chatlog[message.type + message.id];
        if (thisChatlog) {
            //避免浏览器多窗口时聊天记录重复保存
            var nosame;
            layui.each(thisChatlog, function (index, item) {
                if ((item.timestamp === message.timestamp
                    && item.type === message.type
                    && item.id === message.id
                    && item.content === message.content)) {
                    nosame = true;
                }
            });
            if (!(nosame || message.fromid == cache.mine.id)) {
                thisChatlog.push(message);
            }
            if (message.msgType == 3 || message.msgType == 4) {
                if (message.fromid == cache.mine.id) {
                    thisChatlog.push(message);
                }
            }
            if (thisChatlog.length > MAX_ITEM) {
                thisChatlog.shift();
            }
        } else {
            local.chatlog[message.type + message.id] = [message];
        }
        layui.data('layim', {
            key: cache.mine.id
            , value: local
        });
    };

    // 上线重新排序
    var sortSetOnline = function (id) {
        var _dom = $('.layim-list-friend li.layim-friend' + id);
        if (_dom.prev().length > 0) {
            var _html = _dom.prop('outerHTML');
            var list = _dom.parent();
            var index = _dom.data('index');
            var _list = _dom.attr('data-list') || _dom.index();
            _dom.remove();
            list.prepend(_html);

            // 更新缓存
            var item = layui.layim.cache().friend[index].list.splice(_list, 1);
            layui.layim.cache().friend[index].list.unshift(item[0]);
        }
    };

    // 下线重新排序
    var sortSetOffline = function (id) {
        var _dom = $('.layim-list-friend li.layim-friend' + id);
        if (_dom.next().find('img').hasClass('layim-list-gray') === false) {
            var _html = _dom.prop('outerHTML');
            var _index = _dom.data('index');
            var _list = _dom.attr('data-list') || _dom.index();
            $.each(_dom.nextAll(), function (index, item) {
                if ($(item).find('img').hasClass('layim-list-gray') === true) {
                    _dom.remove();
                    $(item).before(_html);
                    // 更新缓存
                    var it = layui.layim.cache().friend[_index].list.splice(_list, 1);
                    $.each(layui.layim.cache().friend[_index].list, function (i, t) {
                        if (t.status === "offline") {
                            layui.layim.cache().friend[_index].list.splice(i, 0, it[0]);
                            return false;
                        }
                    });
                    return false;
                }
            });
        }
    };

    //渲染本地最新聊天记录到相应面板
    var viewChatlog = function () {
        var local = layui.data('layim')[cache.mine.id] || {}
            , thatChat = thisChat(), chatlog = local.chatlog || {}
            , ul = thatChat.elem.find('.layim-chat-main ul');
        layui.each(chatlog[thatChat.data.type + thatChat.data.id], function (index, item) {
            ul.append(laytpl(elemChatMain).render(item));
        });
        chatListMore(false);
    };

    //添加好友或群
    var addList = function (data) {
        if (layimMain) {
            var obj = {}, has, listElem = layimMain.find('.layim-list-' + data.type);
            if (cache[data.type]) {
                if (data.type === 'friend') {
                    layui.each(cache.friend, function (index, item) {
                        if (data.groupid == item.id) {
                            //检查好友是否已经在列表中
                            layui.each(cache.friend[index].list, function (idx, itm) {
                                if (itm.id == data.id) {
                                    return has = true
                                }
                            });
                            // if(has) return layer.msg('好友 ['+ (data.username||'') +'] 已经存在列表中',{anim: 6}, {zIndex: layer.zIndex,
                            //   success: function(layero){
                            //     layer.setTop(layero);
                            //   }});
                            cache.friend[index].list = cache.friend[index].list || [];
                            obj[cache.friend[index].list.length] = data;
                            data.groupIndex = index;
                            cache.friend[index].list.push(data); //在cache的friend里面也增加好友
                            return true;
                        }
                    });
                } else if (data.type === 'group') {
                    //检查群组是否已经在列表中
                    layui.each(cache.group, function (idx, itm) {
                        if (itm.id == data.id) {
                            return has = true
                        }
                    });
                    if (has) return layer.msg('您已是 [' + (data.groupname || '') + '] 的群成员', {anim: 6}, {
                        zIndex: layer.zIndex,
                        success: function (layero) {
                            layer.setTop(layero);
                        }
                    });
                    obj[cache.group.length] = data;
                    cache.group.push(data);
                }
            }

            if (has) return;

            var list = laytpl(listTpl({
                type: data.type
                , item: 'd.data'
                , index: data.type === 'friend' ? 'data.groupIndex' : null
            })).render({data: obj});

            if (data.type === 'friend') {
                var li = listElem.find('>li').eq(data.groupIndex);
                li.find('.layui-layim-list').append(list);
                li.find('.layim-count').html(cache.friend[data.groupIndex].list.length); //刷新好友数量
                //如果初始没有好友
                if (li.find('.layim-null')[0]) {
                    li.find('.layim-null').remove();
                }
            } else if (data.type === 'group') {
                listElem.append(list);
                //如果初始没有群组
                if (listElem.find('.layim-null')[0]) {
                    listElem.find('.layim-null').remove();
                }
            }
        }
    };

    //移出好友或群
    var removeList = function (data) {
        var listElem = layimMain.find('.layim-list-' + data.type);
        var obj = {};
        if (cache[data.type]) {
            if (data.type === 'friend') {
                layui.each(cache.friend, function (index1, item1) {
                    layui.each(item1.list, function (index, item) {
                        if (data.id == item.id) {
                            var li = listElem.find('>li').eq(index1);
                            var list = li.find('.layui-layim-list>li');
                            li.find('.layui-layim-list>li').eq(index).remove();
                            cache.friend[index1].list.splice(index, 1); //从cache的friend里面也删除掉好友
                            li.find('.layim-count').html(cache.friend[index1].list.length); //刷新好友数量
                            //如果一个好友都没了
                            if (cache.friend[index1].list.length === 0) {
                                li.find('.layui-layim-list').html('<li class="layim-null">该分组下已无好友了</li>');
                            }
                            return true;
                        }
                    });
                });
            } else if (data.type === 'group') {
                layui.each(cache.group, function (index, item) {
                    if (data.id == item.id) {
                        listElem.find('>li').eq(index).remove();
                        cache.group.splice(index, 1); //从cache的group里面也删除掉数据
                        //如果一个群组都没了
                        if (cache.group.length === 0) {
                            listElem.html('<li class="layim-null">暂无群组</li>');
                        }
                        return true;
                    }
                });
            }
        }
    };

    //查看更多记录
    var chatListMore = function (check_scroll) {
        var thatChat = thisChat(), chatMain = thatChat.elem.find('.layim-chat-main');
        var ul = chatMain.find('ul');
        var length = ul.find('li').length;

        if (length >= MAX_ITEM) {
            var first = ul.find('li').eq(0);
            if (!ul.prev().hasClass('layim-chat-system')) {
                ul.before('<div class="layim-chat-system"><span layim-event="chatLog">查看更多记录</span></div>');
            }
            if (length > MAX_ITEM) {
                first.remove();
            }
        }
        if (check_scroll == true) {
            if (chatMain.prop("scrollTop") + chatMain.prop("clientHeight") == chatMain.prop("scrollHeight") - 78) {
                chatMain.scrollTop(chatMain[0].scrollHeight + 1000);
                chatMain.find('ul li:last').find('img').load(function () {
                    chatMain.scrollTop(chatMain[0].scrollHeight + 1000);
                });
            }
        } else {
            chatMain.scrollTop(chatMain[0].scrollHeight + 1000);
            // chatMain.find('ul li:last').find('img').load(function(){
            chatMain.find('ul li:last').find('img').on('load', function () {
                chatMain.scrollTop(chatMain[0].scrollHeight + 1000);
            });
        }

    };

    //快捷键发送
    var hotkeySend = function () {
        var thatChat = thisChat(), textarea = thatChat.textarea;
        textarea.focus();
        textarea.off('keydown').on('keydown', function (e) {
            var local = layui.data('layim')[cache.mine.id] || {};
            var keyCode = e.keyCode;
            if (local.sendHotKey === 'Ctrl+Enter') {
                if (e.ctrlKey && keyCode === 13) {
                    sendMessage();
                }
                return;
            }
            if (keyCode === 13) {
                if (e.ctrlKey) {
                    return textarea.val(textarea.val() + '\n');
                }
                if (e.shiftKey) return;
                e.preventDefault();
                sendMessage();
            }
        });
    };
    var alt = ["[weixiao]", "[xixi]", "[haha]", "[keai]", "[kelian]", "[wabi]", "[chijing]", "[haixiu]", "[jiyan]", "[bizui]", "[bishi]", "[aini]", "[lei]", "[touxiao]", "[qinqin]", "[shengbing]", "[taikaixin]", "[baiyan]", "[youhengheng]", "[zuohengheng]", "[xu]", "[shuai]", "[weiqu]", "[tu]", "[haqian]", "[baobao]", "[nu]", "[yinwen]", "[chanzui]", "[baibai]", "[sikao]", "[han]", "[kun]", "[shui]", "[qian]", "[shiwang]", "[ku]", "[se]", "[heng]", "[guzhang]", "[yun]", "[beishang]", "[zhuakuang]", "[heixian]", "[yinxian]", "[numa]", "[hufen]", "[xin]", "[shangxin]", "[zhutou]", "[xiongmao]", "[tuzi]", "[ok]", "[ye]", "[good]", "[no]", "[zan]", "[lai]", "[ruo]", "[caonima]", "[shenma]", "[jiong]", "[fuyun]", "[geili]", "[weiguan]", "[weiwu]", "[aoteman]", "[liwu]", "[zhong]", "[huatong]", "[lazhu]", "[dangao]", "[yh]", "[bizui2]", "[wtbd]", "[ydl]", "[hsns]", "[bgk]", "[jsg]", "[yhs]", "[maida]", "[maishuang]", "[maidan]", "[maixiao]", "[mdkx]", "[dg]", "[ll]", "[xsbbl]", "[sd]", "[mzyx]", "[tlsc]", "[kzz]", "[wtm]", "[dtdl]", "[wjkk]", "[zmmgs]", "[wdtld]", "[wt]", "[tld]", "[yyj]", "[sh]", "[sa]", "[hw]", "[tdm]", "[czwb]", "[mcp]", "[ld]", "[gb]", "[dbz]", "[yw]", "[jlbz]", "[jlby]", "[zmhs]", "[ngsh]", "[yh2]", "[666]", "[zj]", "[byzj]", "[nsxk]", "[zdzj]", "[jdkj]", "[kjl]", "[kd]", "[gx]", "[kjy]", "[byb]", "[xzt]", "[tm]", "[zbw]", "[bxz]", "[cuo]", "[qndy]", "[hql]", "[kma]", "[gs]", "[tt]", "[td]", "[tmd]", "[tnl]", "[ddw]", "[bf]", "[qs]", "[xddzt]", "[tsz]", "[bat]", "[tw]", "[tn]", "[bk]", "[hs]", "[ps]", "[cj]", "[bkn]", "[dzt]", "[sybzd]", "[dt]", "[ty]"];
    //表情库
    var faces = function () {
        var arr = {};
        layui.each(alt, function (index, item) {
            if (index < 72) {
                arr[item] = layui.cache.dir + 'images/face/' + index + '.gif';
            }
            if (index > 71 && index < 114) {
                arr[item] = layui.cache.dir + 'images/face/' + index + '.png';
            }
            if (index > 113) {
                arr[item] = layui.cache.dir + 'images/face/' + index + '.gif';
            }
        });
        return arr;
    }();


    var stope = layui.stope; //组件事件冒泡

    //在焦点处插入内容
    var focusInsert = function (obj, str) {
        var result, val = obj.value;
        obj.focus();
        if (document.selection) { //ie
            result = document.selection.createRange();
            document.selection.empty();
            result.text = str;
        } else {
            result = [val.substring(0, obj.selectionStart), str, val.substr(obj.selectionEnd)];
            obj.focus();
            obj.value = result.join('');
        }
    };

    //事件
    var anim = 'layui-anim-upbit', events = {
        //在线状态
        status: function (othis, e) {
            var hide = function () {
                othis.next().hide().removeClass(anim);
            };
            var type = othis.attr('lay-type');
            if (type === 'show') {
                stope(e);
                othis.next().show().addClass(anim);
                $(document).off('click', hide).on('click', hide);
            } else {
                var prev = othis.parent().prev();
                othis.addClass(THIS).siblings().removeClass(THIS);
                prev.html(othis.find('cite').html());
                prev.removeClass('layim-status-' + (type === 'online' ? 'hide' : 'online'))
                    .addClass('layim-status-' + type);
                layui.each(call.online, function (index, item) {
                    item && item(type);
                });
            }
        }

        //编辑签名
        , sign: function () {
            var input = layimMain.find('.layui-layim-remark');
            input.on('change', function () {
                var value = this.value;
                layui.each(call.sign, function (index, item) {
                    item && item(value);
                });
            });
            input.on('keyup', function (e) {
                var keyCode = e.keyCode;
                if (keyCode === 13) {
                    this.blur();
                }
            });
        }

        //大分组切换
        , tab: function (othis) {
            var index, main = '.layim-tab-content';
            var tabs = layimMain.find('.layui-layim-tab>li');
            typeof othis === 'number' ? (
                index = othis
                    , othis = tabs.eq(index)
            ) : (
                index = othis.index()
            );
            index > 2 ? tabs.removeClass(THIS) : (
                events.tab.index = index
                    , othis.addClass(THIS).siblings().removeClass(THIS)
            )
            layimMain.find(main).eq(index).addClass(SHOW).siblings(main).removeClass(SHOW);
        }

        //展开联系人分组
        , spread: function (othis) {
            var type = othis.attr('lay-type');
            var spread = type === 'true' ? 'false' : 'true';
            var local = layui.data('layim')[cache.mine.id] || {};
            othis.next()[type === 'true' ? 'removeClass' : 'addClass'](SHOW);
            local['spread' + othis.parent().index()] = spread;
            layui.data('layim', {
                key: cache.mine.id
                , value: local
            });
            othis.attr('lay-type', spread);
            othis.find('.layui-icon').html(spread === 'true' ? '&#xe61a;' : '&#xe602;');
        }

        //打开搜索栏
        , search: function (othis) {
            var search = layimMain.find('.layui-layim-search');
            search.show();
        }

        //关闭搜索
        , closeSearch: function (othis) {
            othis.parent().hide();
            events.tab(events.tab.index | 0);
        }
        //搜索
        , search: function (othis) {
            var search = layimMain.find('.layui-layim-search');
            var main = layimMain.find('#layui-layim-search');
            var input = search.find('input'), find = function (e) {

            };
            search.show();
            input.focus();
            input.off('keyup', find).on('keyup', find);
        }
        , memberSearch: function () {
            var search = layimMain.find('.layui-layim-search');
            var input = search.find('input');
            var val = input.val().replace(/\s/);
            if (val === '') {
                events.tab(events.tab.index | 0);
            } else {
                $.post("chat/find-member", {name: val}, function (result) {
                    var main = layimMain.find('#layui-layim-search');
                    var html = '';
                    if (result.data.length > 0) {
                        for (var l = 0; l < result.data.length; l++) {
                            //查找是否在列表中
                            var _dom = $('.layui-layim-main .layim-list-friend').find('.layim-friendmember_' + result.data[l].id);
                            if (_dom.length <= 0) {
                                addList({
                                    type: 'friend'
                                    , avatar: result.data[l].avatar
                                    , username: result.data[l].username
                                    , groupid: result.data[l].group_id
                                    , id: 'member_' + result.data[l].id
                                    , sign: result.data[l].sign
                                    , status: result.data[l].status
                                    , level: result.data[l].level
                                });
                            }
                            var data = [], friend = layui.layim.cache().friend || [];
                            for (var i = 0; i < friend.length; i++) {
                                for (var k = 0; k < (friend[i].list || []).length; k++) {
                                    if (friend[i].list[k].username.indexOf(val) !== -1) {
                                        friend[i].list[k].type = 'friend';
                                        friend[i].list[k].index = i;
                                        friend[i].list[k].list = k;
                                        data.push(friend[i].list[k]);
                                    }
                                }
                            }
                            html += '<li layim-event="chat" data-type="friend" data-index="' + data[l].index + '" data-list="' + data[l].list + '"><img src="' + result.data[l].avatar + '"><span>' + result.data[l].username + '</span><p>' + result.data[l].sign + '</p></li>';
                        }
                    } else {
                        html = '<li class="layim-null">无搜索结果</li>';
                    }
                    main.html(html);
                    events.tab(3);
                });
            }
        }
        //消息盒子
        , msgbox: function () {
            var msgboxElem = layimMain.find('.layim-tool-msgbox');
            layer.close(events.msgbox.index);
            msgboxElem.find('span').removeClass(ANIM_MSG).html('');
            return events.msgbox.index = layer.open({
                type: 2
                , title: '消息盒子'
                , shade: false
                , maxmin: true
                , area: ['600px', '520px']
                , skin: 'layui-box layui-layer-border'
                , resize: false
                , content: cache.base.msgbox
            });
        }

        //弹出查找页面
        , find: function () {
            layer.close(events.find.index);
            return events.find.index = layer.open({
                type: 2
                , title: '查找'
                , shade: false
                , maxmin: true
                , area: ['1000px', '520px']
                , skin: 'layui-box layui-layer-border'
                , resize: false
                , content: cache.base.find
            });
        }

        //弹出更换背景
        , skin: function () {
            layer.open({
                type: 1
                , title: '更换背景'
                , shade: false
                , area: '300px'
                , skin: 'layui-box layui-layer-border'
                , id: 'layui-layim-skin'
                , resize: false
                , content: laytpl(elemSkinTpl).render({
                    skin: cache.base.skin
                })
                , zIndex: layer.zIndex
                , success: function (layero) {
                    layer.setTop(layero);
                }
            });
        }

        //关于
        , about: function () {
            layer.alert('版本： ' + v + '<br>版权所有：<a href="http://layim.layui.com" target="_blank">layim.layui.com</a>', {
                title: '关于 LayIM'
                , shade: false
            });
        }
        , withdrew: function () {
            layer.close(events.withdrew.index);
            layer.closeAll('tips');
            return events.withdrew.index = layer.prompt({
                title: '申请提现', formType: 3, zIndex: layer.zIndex, success: function (layero) {
                    layer.setTop(layero);
                }
            }, function (number, index) {
                var account = $('#platform_account').val();
                if (account == '') {
                    layer.msg('请输入平台账号', {
                        zIndex: layer.zIndex,
                        success: function (layero) {
                            layer.setTop(layero);
                        }
                    });
                    return;
                }
                ;
                if (!check(number)) {
                    layer.msg('请输入正确的金额', {
                        zIndex: layer.zIndex,
                        success: function (layero) {
                            layer.setTop(layero);
                        }
                    });
                    return;
                }
                if (number < 0.01) {
                    layer.msg('金额不能低于0.01', {
                        zIndex: layer.zIndex,
                        success: function (layero) {
                            layer.setTop(layero);
                        }
                    });
                    return;
                }
                layer.close(index);
                $.post("/chat/withdrew", {amount: number, account: account}, function (result) {
                    parent.layer.msg(result.message, {
                        zIndex: layer.zIndex,
                        success: function (layero) {
                            layer.setTop(layero);
                        }
                    });
                });
            }), $(".layui-layer-prompt .layui-layer-content").append("<br/><input type=\"text\" id= \"platform_account\" class=\"layui-layer-input\" placeholder=\"平台账号\"/>"), $(".layui-layer-prompt .layui-layer-content").children(":first").attr('placeholder', '金额');

        }
        , integral_exchange: function () {
            layer.close(events.integral_exchange.index);
            layer.closeAll('tips');
            return events.integral_exchange.index = layer.open({
                type: 2
                , title: '积分兑换'
                , shade: false
                , maxmin: true
                , area: ['400px', '500px']
                , skin: 'layui-box layui-layer-border'
                , resize: true
                , content: '/chat/integral-exchange'
                , zIndex: layer.zIndex
                , success: function (layero) {
                    layer.setTop(layero);
                }
            });
        }
        , friend_show: function () {
            layer.close(events.friend_show.index);
            layer.closeAll('tips');
            return events.friend_show.index = layer.open({
                type: 2
                , title: '朋友圈'
                , shade: false
                , maxmin: true
                , area: ['500px', '600px']
                , skin: 'layui-box layui-layer-border'
                , resize: true
                , content: '/chat/friend-show'
                , zIndex: layer.zIndex
                , success: function (layero) {
                    layer.setTop(layero);
                }
            });
        }
        , account_log: function () {
            layer.close(events.account_log.index);
            layer.closeAll('tips');
            return events.account_log.index = layer.open({
                type: 2
                , title: '账户记录'
                , shade: false
                , maxmin: true
                , area: ['800px', '640px']
                , skin: 'layui-box layui-layer-border'
                , resize: true
                , content: '/chat/account-log'
                , zIndex: layer.zIndex
                , success: function (layero) {
                    layer.setTop(layero);
                }
            });
        }
        , active: function () {
            layer.close(events.active.index);
            layer.closeAll('tips');
            return events.active.index = layer.open({
                type: 2
                , title: '最新活动'
                , shade: false
                , maxmin: true
                , area: ['400px', '670px']
                , skin: 'layui-box layui-layer-border'
                , resize: true
                , content: cache.base.active
                , zIndex: layer.zIndex
                , success: function (layero) {
                    layer.setTop(layero);
                }
            });
        }
        , bulletin: function () {
            layer.close(events.bulletin.index);
            layer.closeAll('tips');
            return events.bulletin.index = layer.open({
                type: 2
                , title: '官方公告'
                , shade: false
                , maxmin: true
                , area: ['400px', '670px']
                , skin: 'layui-box layui-layer-border'
                , resize: true
                , content: cache.base.bulletin
                , zIndex: layer.zIndex
                , success: function (layero) {
                    layer.setTop(layero);
                }
            });
        }
        , game: function (othis) {
            layer.close(events.game.index);
            layer.closeAll('tips');
            layui.each(cache.group, function (index, item) {
                if (item.id == othis.data('id')) {
                    return events.game.index = layer.open({
                        type: 2
                        , title: '祝君中奖'
                        , shade: false
                        , maxmin: true
                        , area: ['400px', '670px']
                        , skin: 'layui-box layui-layer-border'
                        , resize: true
                        , content: item.betting_url
                        , zIndex: layer.zIndex
                        , success: function (layero) {
                            layer.setTop(layero);
                        }
                    });
                }
            });
        }
        , video: function (othis) {
            layer.close(events.video.index);
            layer.closeAll('tips');
            layui.each(cache.group, function (index, item) {
                if (item.id == othis.data('id')) {
                    return events.video.index = layer.open({
                        type: 2
                        , id: 'lottery_video'
                        , title: '开奖直播'
                        , shade: false
                        , maxmin: true
                        , area: ['800px', '590px']
                        , skin: 'layui-box layui-layer-border'
                        , resize: true
                        , content: item.video_url
                        , zIndex: layer.zIndex
                        , success: function (layero) {
                            layer.setTop(layero);
                        }
                    });
                }
            });
        }
        , menu: function () {
            var hide = function () {
                layer.closeAll('tips');
            };
            var html = '<ul>';
            cache.mine.id.match('manager') === null ? html += '<li class="layui-icon layim-tool-friend-show" layim-event="friend_show" title="朋友圈" style="line-height: 38px">&#xe64a;&nbsp;&nbsp; 朋友圈</li><li class="layui-icon layim-tool-withdrew" layim-event="withdrew" title="申请提现" style="line-height: 38px">&#xe609; 申请提现</li><li class="layui-icon layim-tool-account-log" layim-event="account_log" title="账户记录" style="line-height: 38px">&#xe65e; 账户记录</li><li class="layui-icon layim-tool-integral-exchange" layim-event="integral_exchange" title="积分兑换" style="line-height: 38px">&#xe62a; 积分兑换</li>' : '';
            html += '<li class="layui-icon layim-tool-active" layim-event="active" title="最新活动" style="line-height: 38px">&#xe756; 最新活动</li><li class="layui-icon layim-tool-bulletin" layim-event="bulletin" title="官方公告" style="line-height: 38px">&#xe60a; 官方公告</li></ul>';
            layer.tips(html, this, {
                tips: 1
                , time: 0
                , anim: 5
                , fixed: true
                , skin: 'layui-box layui-layim-menu'
                , success: function (layero) {
                    layer.setTop(layero);
                    var stopmp = function (e) {
                        stope(e);
                    };
                    layero.off('mousedown', stopmp).on('mousedown', stopmp);
                }
                , zIndex: layer.zIndex
            });
            $(document).off('mousedown', hide).on('mousedown', hide);
            $(window).off('resize', hide).on('resize', hide);
        }
        , plan: function () {
            $(this).find('.layui-badge-dot').addClass('layui-hide');
            return events.plan.index = layer.open({
                id: 'plan_box_',
                type: 2
                , title: '在线计划'
                , shade: false
                , maxmin: true
                , area: ['400px', '670px']
                , skin: 'layui-box layui-layer-border'
                , resize: true
                , content: cache.base.plan
                , zIndex: layer.zIndex
                , success: function (layero) {
                    layer.setTop(layero);
                }
            });
        }
        , planselect: function () {
            $.ajax({
                url: "chat/get-link-room",
                type: 'POST',
                data: '',
                processData: false,
                contentType: false,
                success: function (res) {
                    var select_html = '';
                    if (res.data) {
                        layui.each(res.data, function (index, item) {
                            var name = item.notice ? item.notice : item.name;
                            select_html += '<label class="checkbox-inline"><input type="checkbox" value="' + item.id + '"><span>' + name + '</span></label>'
                        });
                    }
                    layer.open({
                        content: '<p>请选择房间</p><p id="plan_select">' + select_html + '</p>'
                        , title: '实时计划'
                        , zIndex: layer.zIndex
                        , success: function (layero) {
                            layer.setTop(layero);
                        }
                        , yes: function (index, layero) {
                            $(layero).find('input:checkbox:checked').each(function () {
                                layer.open({
                                    id: 'plan_box_' + $(this).val(),
                                    type: 2
                                    , title: $(this).next().text()
                                    , shade: false
                                    , maxmin: true
                                    , area: ['400px', '670px']
                                    , skin: 'layui-box layui-layer-border'
                                    , resize: true
                                    , content: 'chat/plan?id=' + $(this).val()
                                    , zIndex: layer.zIndex
                                    , success: function (layero) {
                                        layer.setTop(layero);
                                    }
                                });
                            });
                            layer.close(index);
                        }
                    });
                }
            });
        }
        , rank_btn: function () {
            layer.open({
                content: '请选择'
                , title: '排行榜'
                , zIndex: layer.zIndex
                , btn: ['日榜', '月榜']
                , btn1: function (index, layero) {
                    layer.close(index);
                    events.rank('day');
                }, btn2: function (index, layero) {
                    events.rank('month');
                }
                , success: function (layero) {
                    layer.setTop(layero);
                }
            });
        }
        , rank: function (type) {
            return events.rank.index = layer.open({
                id: 'rank-box',
                type: 2
                , title: '排行榜'
                , shade: false
                , maxmin: true
                , area: ['1000px', '560px']
                , skin: 'layui-box layui-layer-border'
                , resize: false
                , content: cache.base.rank + '?type=' + type
                , zIndex: layer.zIndex
                , success: function (layero) {
                    layer.setTop(layero);
                }
            });
        }

        //生成换肤
        , setSkin: function (othis) {
            var src = othis.attr('src');
            var local = layui.data('layim')[cache.mine.id] || {};
            local.skin = src;
            if (!src) delete local.skin;
            layui.data('layim', {
                key: cache.mine.id
                , value: local
            });
            try {
                layimMain.css({
                    'background-image': src ? 'url(' + src + ')' : 'none'
                });
                layimChat.css({
                    'background-image': src ? 'url(' + src + ')' : 'none'
                });
            } catch (e) {
            }
            layui.each(call.setSkin, function (index, item) {
                var filename = (src || '').replace(layui.cache.dir + 'css/modules/layim/skin/', '');
                item && item(filename, src);
            });
        }

        //弹出聊天面板
        , chat: function (othis) {
            var local = layui.data('layim')[cache.mine.id] || {};
            var type = othis.data('type'), index = othis.data('index');
            var list = othis.attr('data-list') || othis.index(), data = {};
            if (type === 'friend') {
                data = cache[type][index].list[list];
            } else if (type === 'group') {
                data = cache[type][list];
            } else if (type === 'history') {
                data = (local.history || {})[index] || {};
            }
            data.name = data.name || data.username || data.groupname;
            if (type !== 'history') {
                data.type = type;
            }
            popchat(data);
        }

        //切换聊天
        , tabChat: function (othis) {
            changeChat(othis);
        }
        , revokeMessage: function (othis) {
            layer.confirm('撤回消息？', {
                zIndex: layer.zIndex,
                success: function (layero) {
                    layer.setTop(layero);
                }
            }, function (index) {
                layer.close(index);
                var msg_id = othis.parent().parent().parent().data('cid');
                var thatChat = thisChat();
                $.post("chat/revoke-message", {
                    msg_id: msg_id,
                    type: thatChat.data.type,
                    room_id: thatChat.data.id
                }, function (res) {
                    if (res.code !== '200') {
                        parent.layer.msg(res.message, {
                            zIndex: layer.zIndex,
                            success: function (layero) {
                                layer.setTop(layero);
                            }
                        });
                    }
                });
            });
        }
        , setTop: function (othis) {
            layer.confirm('设为置顶？', {
                zIndex: layer.zIndex,
                success: function (layero) {
                    layer.setTop(layero);
                }
            }, function (index) {
                layer.close(index);
                var msg_id = othis.parent().parent().parent().data('cid');
                if (!msg_id) msg_id = othis.parent().parent().parent().parent().data('cid');
                var thatChat = thisChat();
                $.post("chat/set-top", {msg_id: msg_id, room_id: thatChat.data.id}, function (res) {
                    if (res.code !== '200') {
                        parent.layer.msg(res.message, {
                            zIndex: layer.zIndex,
                            success: function (layero) {
                                layer.setTop(layero);
                            }
                        });
                    }
                }).fail(function (xhr) {
                    parent.layer.msg(xhr.responseJSON.message, {
                        zIndex: layer.zIndex,
                        success: function (layero) {
                            layer.setTop(layero);
                        }
                    });
                });
            });
        }
        , cancelTop: function (othis) {
            layer.confirm('取消置顶？', {
                zIndex: layer.zIndex,
                success: function (layero) {
                    layer.setTop(layero);
                }
            }, function (index) {
                layer.close(index);
                var thatChat = thisChat();
                $.post("chat/cancel-top", {room_id: thatChat.data.id}, function (res) {
                    if (res.code !== '200') {
                        parent.layer.msg(res.message, {
                            zIndex: layer.zIndex,
                            success: function (layero) {
                                layer.setTop(layero);
                            }
                        });
                    }
                }).fail(function (xhr) {
                    parent.layer.msg(xhr.responseJSON.message, {
                        zIndex: layer.zIndex,
                        success: function (layero) {
                            layer.setTop(layero);
                        }
                    });
                });
            });
        }
        //关闭聊天列表
        , closeChat: function (othis, e) {
            changeChat(othis.parent(), 1);
            stope(e);
        }, closeThisChat: function () {
            changeChat(null, 1);
        }
        , information: function (othis, e) {
            if (cache.mine.id.match('manager')) {
                layer.close(events.information.index);
                var id = othis.data('id');
                return events.information.index = layer.open({
                    type: 2
                    , title: '会员资料'
                    , shade: false
                    , maxmin: false
                    , area: ['400px', '670px']
                    , skin: 'layui-box layui-layer-border'
                    , resize: true
                    , content: cache.base.information + '?id=' + id
                    , zIndex: layer.zIndex
                    , success: function (layero) {
                        layer.setTop(layero);
                    }
                });
            }
        }
        , myInformation: function (othis, e) {
            layer.close(events.myInformation.index);
            return events.myInformation.index = layer.open({
                type: 2
                , title: '我的资料'
                , shade: false
                , maxmin: true
                , area: ['400px', '670px']
                , skin: 'layui-box layui-layer-border'
                , resize: true
                , content: cache.base.myInformation
                , zIndex: layer.zIndex
                , success: function (layero) {
                    layer.setTop(layero);
                }
            });
        }
        //展开群组成员
        , groupMembers: function (othis, e) {
            var icon = othis.find('.layui-icon'), hide = function () {
                icon.html('&#xe61a;');
                othis.data('down', null);
                layer.close(events.groupMembers.index);
            }, stopmp = function (e) {
                stope(e)
            };

            if (othis.data('down')) {
                hide();
            } else {
                icon.html('&#xe619;');
                othis.data('down', true);
                events.groupMembers.index = layer.tips('<ul class="layim-members-list"></ul>', othis, {
                    tips: 3
                    , time: 0
                    , anim: 5
                    , fixed: true
                    , skin: 'layui-box layui-layim-members'
                    , zIndex: layer.zIndex
                    , success: function (layero) {
                        layer.setTop(layero);
                        var members = cache.base.members || {}, thatChat = thisChat()
                            , ul = layero.find('.layim-members-list'), li = '', membersCache = {}
                            , hasFull = layimChat.find('.layui-layer-max').hasClass('layui-layer-maxmin')
                            , listNone = layimChat.find('.layim-chat-list').css('display') === 'none';
                        if (hasFull) {
                            ul.css({
                                width: $(window).width() - 22 - (listNone || 200)
                            });
                        } else {
                            ul.css({
                                width: thatChat.elem.width() - 22 - (listNone || 0)
                            });
                        }
                        members.data = $.extend(members.data, {
                            id: thatChat.data.id
                        });
                        post(members, function (res) {
                            layui.each(res.list, function (index, item) {
                                li += '<li data-uid="' + item.id + '"><a href="javascript:;"><img src="' + item.avatar + '" class="';
                                if (item.status === 'offline') li += 'layim-list-gray';
                                li += '"><cite>' + item.username + '</cite></a></li>';
                                membersCache[item.id] = item;
                            });
                            li += '<hr/>';
                            layui.each(res.members, function (index, item) {
                                li += '<li data-uid="' + item.id + '"><a href="javascript:;"><img src="' + item.avatar + '" class="';
                                if (item.status === 'offline') li += 'layim-list-gray';
                                li += '"><cite>' + item.username + '</cite></a></li>';
                                membersCache[item.id] = item;
                            });
                            ul.html(li);

                            //获取群员
                            othis.find('.layim-chat-members').html('(' + (res.members.length + res.list.length) + ')');

                            //私聊
                            ul.find('li').on('click', function () {
                                var uid = $(this).data('uid'), info = membersCache[uid];
                                var isManager = cache.mine.id.match('manager');
                                if (isManager) {
                                    if (uid.match('manager')) {
                                        return;
                                    }
                                } else {
                                    if (uid.match('member')) {
                                        return;
                                    }
                                }
                                popchat({
                                    name: info.username
                                    , type: 'friend'
                                    , avatar: info.avatar
                                    , id: info.id
                                    , status: info.status
                                    , sign: info.sign
                                });
                                hide();
                            });

                            layui.each(call.members, function (index, item) {
                                item && item(res);
                            });
                        });
                        layero.on('mousedown', function (e) {
                            stope(e);
                        });
                    }
                });
                $(document).off('mousedown', hide).on('mousedown', hide);
                $(window).off('resize', hide).on('resize', hide);
                othis.off('mousedown', stopmp).on('mousedown', stopmp);
            }
        }

        //发送聊天内容
        , send: function () {
            sendMessage();
        }

        //设置发送聊天快捷键
        , setSend: function (othis, e) {
            var box = events.setSend.box = othis.siblings('.layim-menu-box')
                , type = othis.attr('lay-type');

            if (type === 'show') {
                stope(e);
                box.show().addClass(anim);
                $(document).off('click', events.setSendHide).on('click', events.setSendHide);
            } else {
                othis.addClass(THIS).siblings().removeClass(THIS);
                var local = layui.data('layim')[cache.mine.id] || {};
                local.sendHotKey = type;
                layui.data('layim', {
                    key: cache.mine.id
                    , value: local
                });
                events.setSendHide(e, othis.parent());
            }
        }, setSendHide: function (e, box) {
            (box || events.setSend.box).hide().removeClass(anim);
        }

        //表情
        , face: function (othis, e) {
            var content = '', thatChat = thisChat();
            layui.each(alt, function (index, item) {
                if (index < 72) {
                    content += '<li title="' + item + '"><img src="' + layui.cache.dir + 'images/face/' + index + '.gif' + '"></li>';
                }
            });
            // content = '<ul class="layui-clear layim-face-list">' + content + '</ul><ul id="facenav"><li layim-event="face_moren" class="layui-bg-blue">默认</li><li layim-event="face_tiezhi">贴纸2</li><li layim-event="face_gif">GIF</li></ul>';
            content = '<ul class="layui-clear layim-face-list">' + content + '</ul><ul id="facenav"><li layim-event="face_moren" class="layui-bg-blue">默认</li></ul>';
            events.face.index = layer.tips(content, othis, {
                tips: 1
                , time: 0
                , fixed: true
                , skin: 'layui-box layui-layim-face'
                , zIndex: layer.zIndex
                , success: function (layero) {
                    layer.setTop(layero);
                    layero.find('.layim-face-list>li').on('mousedown', function (e) {
                        stope(e);
                    }).on('click', function () {
                        focusInsert(thatChat.textarea[0], 'face' + this.title + ' ');
                        layer.close(events.face.index);
                    });
                }
            });

            // $(document).off('mousedown', events.faceHide).on('mousedown', events.faceHide);
            $(window).off('resize', events.faceHide).on('resize', events.faceHide);
            stope(e);

        }, faceHide: function () {
            layer.close(events.face.index);
            layer.close(events.face_tiezhi.index);
            layer.close(events.face_gif.index);
        }
        , face_moren: function (othis, e) {
            var thatChat = thisChat();
            thatChat.elem.find('.layim-chat-footer .layim-chat-tool .layim-tool-face').trigger('click')
        }
        , face_tiezhi: function (othis, e) {
            var content = '', thatChat = thisChat();
            layui.each(alt, function (index, item) {
                if (index > 71 && index < 114) {
                    content += '<li title="' + item + '"><img src="' + layui.cache.dir + 'images/face/' + index + '.png' + '"></li>';
                }
            });
            // content = '<ul class="layui-clear layim-face-list2">' + content + '</ul><ul id="facenav"><li layim-event="face_moren">默认</li><li layim-event="face_tiezhi" class="layui-bg-blue">贴纸2</li><li layim-event="face_gif">GIF</li></ul>';
            content = '<ul class="layui-clear layim-face-list2">' + content + '</ul><ul id="facenav"><li layim-event="face_moren">默认</li></ul>';
            events.face_tiezhi.index = layer.tips(content, thatChat.elem.find('.layim-tool-face'), {
                tips: 1
                , time: 0
                , fixed: true
                , skin: 'layui-box layui-layim-face2'
                , zIndex: layer.zIndex
                , success: function (layero) {
                    layer.setTop(layero);
                    layero.find('.layim-face-list2>li').on('mousedown', function (e) {
                        stope(e);
                    }).on('click', function () {
                        focusInsert(thatChat.textarea[0], 'face' + this.title + ' ');
                        layer.close(events.face_tiezhi.index);
                    });
                }
            });

            $(window).off('resize', events.faceHide).on('resize', events.faceHide);
            stope(e);
        }
        , face_gif: function (othis, e) {
            var content = '', thatChat = thisChat();
            layui.each(alt, function (index, item) {
                if (index > 113) {
                    content += '<li title="' + item + '"><img src="' + layui.cache.dir + 'images/face/' + index + '.gif' + '"></li>';
                }
            });
            // content = '<ul class="layui-clear layim-face-list2">' + content + '</ul><ul id="facenav"><li layim-event="face_moren">默认</li><li layim-event="face_tiezhi">贴纸2</li><li layim-event="face_gif" class="layui-bg-blue">GIF</li></ul>';
            content = '<ul class="layui-clear layim-face-list2">' + content + '</ul><ul id="facenav"><li layim-event="face_moren">默认</li></ul>';
            events.face_gif.index = layer.tips(content, thatChat.elem.find('.layim-tool-face'), {
                tips: 1
                , time: 0
                , fixed: true
                , skin: 'layui-box layui-layim-face2'
                , zIndex: layer.zIndex
                , success: function (layero) {
                    layer.setTop(layero);
                    layero.find('.layim-face-list2>li').on('mousedown', function (e) {
                        stope(e);
                    }).on('click', function () {
                        focusInsert(thatChat.textarea[0], 'face' + this.title + ' ');
                        layer.close(events.face_gif.index);
                    });
                }
            });

            $(window).off('resize', events.faceHide).on('resize', events.faceHide);
            stope(e);
        }

        //图片或一般文件
        , image: function (othis) {
            var type = othis.data('type') || 'images', api = {
                images: 'uploadImage'
                , file: 'uploadFile'
            }
                , thatChat = thisChat(), conf = cache.base[api[type]] || {};

            layui.upload.render({
                url: conf.url || ''
                , method: conf.type
                , elem: othis.find('input')[0]
                , accept: type
                , done: function (res) {
                    if (res.code == 0) {
                        res.data = res.data || {};
                        if (type === 'images') {
                            focusInsert(thatChat.textarea[0], 'img[' + (res.data.src || '') + ']');
                        } else if (type === 'file') {
                            focusInsert(thatChat.textarea[0], 'file(' + (res.data.src || '') + ')[' + (res.data.name || '下载文件') + ']');
                        }
                        sendMessage(2);
                    } else {
                        layer.msg(res.msg || '上传失败', {
                            zIndex: layer.zIndex,
                            success: function (layero) {
                                layer.setTop(layero);
                            }
                        });
                    }
                }
            });
        }
        // 发红包
        , redpackage: function (othis) {
            var thatChat = thisChat();
            layer.close(events.redpackage.index);
            layer.closeAll('tips');
            if (layui.layim.cache().mine.id.match("manager")) {
                return events.redpackage.index = layer.open({
                    content: '请选择类型'
                    , title: '发红包'
                    , zIndex: layer.zIndex
                    , btn: ['普通红包', '运气红包']
                    , btn1: function (index, layero) {
                        layer.close(index);
                        events.redpackage_normal('redpackage');
                    }, btn2: function (index, layero) {
                        events.redpackage_normal('redpackage-lucky');
                    }
                    , success: function (layero) {
                        layer.setTop(layero);
                    }
                });
            }
            return events.redpackage.index = layer.open({
                type: 2
                , title: '发红包'
                , shade: false
                , area: ['320px', '420px']
                , skin: 'layui-box layui-layer-border'
                , resize: true
                , content: 'chat/redpackage?room_id=' + thatChat.data.id
                , zIndex: layer.zIndex
                , success: function (layero, index) {
                    layer.setTop(layero);
                    layer.iframeAuto(index);
                }
            });
        }
        // 红包表单
        , redpackage_normal: function (type) {
            var thatChat = thisChat();
            layer.close(events.redpackage_normal.index);
            layer.closeAll('tips');
            return events.redpackage_normal.index = layer.open({
                type: 2
                , title: '发红包'
                , shade: false
                , area: ['320px', '420px']
                , skin: 'layui-box layui-layer-border'
                , resize: true
                , content: 'chat/' + type + '?room_id=' + thatChat.data.id
                , zIndex: layer.zIndex
                , success: function (layero, index) {
                    layer.setTop(layero);
                    layer.iframeAuto(index);
                }
            });
        }
        // 定时消息
        , notice: function (othis) {
            var thatChat = thisChat();
            layer.close(events.redpackage.index);
            layer.closeAll('tips');
            return events.notice.index = layer.prompt({
                title: thatChat.data.groupname + ' 定时消息',
                formType: 2,
                zIndex: layer.zIndex,
                success: function (layero) {
                    layer.setTop(layero);
                }
            }, function (content, index) {
                var time = $('input[name="time"]:checked').val();
                sendMessage(1, content);
                setInterval(function () {
                    sendMessage(1, content);
                }, time);
                layer.msg('设置成功，如需取消请刷新页面', {
                    zIndex: layer.zIndex,
                    success: function (layero) {
                        layer.setTop(layero);
                    }
                });
                layer.close(index);
            }), $(".layui-layer-prompt .layui-layer-content").append("<br/><label class=\"radio-inline\"><input type=\"radio\" name=\"time\" class=\"radio\" value=\"300000\" checked=''>五分钟</label><label class=\"radio-inline\"><input type=\"radio\" name=\"time\" class=\"radio\" value=\"600000\">十分钟</label><label class=\"radio-inline\"><input type=\"radio\" name=\"time\" class=\"radio\" value=\"900000\">十五分钟</label><label class=\"radio-inline\"><input type=\"radio\" name=\"time\" class=\"radio\" value=\"1800000\">三十分钟</label>"), $(".layui-layer-prompt .layui-layer-content").children(":first").attr('placeholder', '消息内容');
        }
        , box_plan: function (othis) {
            var plan_list = $('.layim-plan-list');
            var is_show = plan_list.is(':visible');
            if (is_show) {
                plan_list.hide();
            } else {
                plan_list.show();
            }
        }
        //音频和视频
        , media: function (othis) {
            var type = othis.data('type'), text = {
                audio: '音频'
                , video: '视频'
            }, thatChat = thisChat()

            layer.prompt({
                title: '请输入网络' + text[type] + '地址'
                , shade: false
                , offset: [
                    othis.offset().top - $(window).scrollTop() - 158 + 'px'
                    , othis.offset().left + 'px'
                ]
            }, function (src, index) {
                focusInsert(thatChat.textarea[0], type + '[' + src + ']');
                sendMessage();
                layer.close(index);
            });
        }

        //扩展工具栏
        , extend: function (othis) {
            var filter = othis.attr('lay-filter')
                , thatChat = thisChat();

            layui.each(call['tool(' + filter + ')'], function (index, item) {
                item && item.call(othis, function (content) {
                    focusInsert(thatChat.textarea[0], content);
                }, sendMessage, thatChat);
            });
        }

        //播放音频
        , playAudio: function (othis) {
            var audioData = othis.data('audio')
                , audio = audioData || document.createElement('audio')
                , pause = function () {
                audio.pause();
                othis.removeAttr('status');
                othis.find('i').html('&#xe652;');
            };
            if (othis.data('error')) {
                return layer.msg('播放音频源异常');
            }
            if (!audio.play) {
                return layer.msg('您的浏览器不支持audio');
            }
            if (othis.attr('status')) {
                pause();
            } else {
                audioData || (audio.src = othis.data('src'));
                audio.play();
                othis.attr('status', 'pause');
                othis.data('audio', audio);
                othis.find('i').html('&#xe651;');
                //播放结束
                audio.onended = function () {
                    pause();
                };
                //播放异常
                audio.onerror = function () {
                    layer.msg('播放音频源异常');
                    othis.data('error', true);
                    pause();
                };
            }
        }

        //播放视频
        , playVideo: function (othis) {
            var videoData = othis.data('src')
                , video = document.createElement('video');
            if (!video.play) {
                return layer.msg('您的浏览器不支持video');
            }
            layer.close(events.playVideo.index);
            events.playVideo.index = layer.open({
                type: 1
                ,
                title: '播放视频'
                ,
                area: ['460px', '300px']
                ,
                maxmin: true
                ,
                shade: false
                ,
                content: '<div style="background-color: #000; height: 100%;"><video style="position: absolute; width: 100%; height: 100%;" src="' + videoData + '" loop="loop" autoplay="autoplay"></video></div>'
                ,
                zIndex: layer.zIndex
                ,
                success: function (layero) {
                    layer.setTop(layero);
                }
            });
        }

        //聊天记录
        , chatLog: function (othis) {
            var thatChat = thisChat();
            if (!cache.base.chatLog) {
                return layer.msg('未开启更多聊天记录', {
                    zIndex: layer.zIndex,
                    success: function (layero) {
                        layer.setTop(layero);
                    }
                });
            }
            layer.close(events.chatLog.index);
            return events.chatLog.index = layer.open({
                type: 2
                , maxmin: true
                , title: '与 ' + thatChat.data.name + ' 的聊天记录'
                , area: ['450px', '100%']
                , shade: false
                , offset: 'rb'
                , skin: 'layui-box'
                , anim: 2
                , id: 'layui-layim-chatlog'
                , content: cache.base.chatLog + '?id=' + thatChat.data.id + '&type=' + thatChat.data.type
                , zIndex: layer.zIndex
                , success: function (layero) {
                    layer.setTop(layero);
                }
            });
        }

        //历史会话右键菜单操作
        , menuHistory: function (othis, e) {
            var local = layui.data('layim')[cache.mine.id] || {};
            var parent = othis.parent(), type = othis.data('type');
            var hisElem = layimMain.find('.layim-list-history');
            var none = '<li class="layim-null">暂无历史会话</li>'

            if (type === 'one') {
                var history = local.history;
                delete history[parent.data('index')];

                local.history = history;

                layui.data('layim', {
                    key: cache.mine.id
                    , value: local
                });

                //删除 DOM
                $('.layim-list-history li.layim-' + parent.data('index')).remove();

                if (hisElem.find('li').length === 0) {
                    hisElem.html(none);
                }

            } else if (type === 'all') {
                delete local.history;
                layui.data('layim', {
                    key: cache.mine.id
                    , value: local
                });
                hisElem.html(none);
            }

            layer.closeAll('tips');
        }
        , atOne: function (othis, e) {
            layer.closeAll('tips');
            var thatChat = thisChat();
            var nickname = $(this).parent().data('nickname');
            if (nickname) {
                atArr.push(othis.parent().data('id'));
                focusInsert(thatChat.textarea[0], '@' + nickname + ' ');
            } else {
                nickname = $(this).find('span').text();
                var text = thatChat.elem.find('.layim-chat-footer .layim-chat-textarea textarea');
                text.val(text.val().replace(/([^@]*)$/, ''));
                atArr.push(othis.data('id'));
                focusInsert(thatChat.textarea[0], nickname + ' ');
            }
            atStatus = false;
        }
        , listenAt: function () {
            $('body').on("input propertychange", '.layim-chat-textarea textarea', function (e) {
                events.search_at();
            });
        },
        search_at: function () {
            var thatChat = thisChat();
            var text_val = thatChat.elem.find('.layim-chat-footer .layim-chat-textarea textarea').val();
            if (text_val) {
                if (text_val.length == 0) {
                    atArr = [];
                    layer.closeAll('tips');
                    return;
                }
                if (text_val.charAt(text_val.length - 1) == '@') {
                    atStatus = true;
                }
                if (text_val.match(/@/) && thatChat.data.type == 'group' && atStatus == true) {
                    var atSearch = text_val.match(/([^@]*)$/)[1];
                    clearTimeout(timer);
                    timer = setTimeout(function () {
                        if (!text_val.match(/@/)) {
                            atArr = [];
                            layer.closeAll('tips');
                            return;
                        }
                        if (!!atCache[atSearch + thatChat.data.id]) {
                            var list_html = '';
                            layui.each(atCache[atSearch + thatChat.data.id], function (index, item) {
                                var avatar = item.avatar ? item.avatar : '/resources/dist/img/profile_small.jpg';
                                list_html += '<li data-id="' + item.id + '" layim-event="atOne"><img src="' + avatar + '" class="at-avatar"><span>' + item.nickname + '</span></li>';
                            });
                            layer.tips('<ul class="layim-at-members-list">' + list_html + '</ul>', '.layui-show .layim-chat-textarea', {
                                tips: 1
                                , time: 0
                                , tipsMore: false
                                , anim: 5
                                , fixed: true
                                , skin: 'layui-box layui-layim-members layui-layim-members-at'
                                , zIndex: layer.zIndex
                                , success: function (layero) {
                                    layer.setTop(layero);
                                }
                            });
                        } else {
                            post({url: 'chat/at-one', data: {name: atSearch, id: thatChat.data.id}}, function (res) {
                                atCache[atSearch + thatChat.data.id] = [];
                                atCache[atSearch + thatChat.data.id] = res;//给缓存对象赋值
                                var list_html = '';
                                layui.each(res, function (index, item) {
                                    var avatar = item.avatar ? item.avatar : '/resources/dist/img/profile_small.jpg';
                                    list_html += '<li data-id="' + item.id + '" layim-event="atOne"><img src="' + avatar + '" class="at-avatar"><span>' + item.nickname + '</span></li>';
                                });
                                layer.tips('<ul class="layim-at-members-list">' + list_html + '</ul>', '.layui-show .layim-chat-textarea', {
                                    tips: 1
                                    , time: 0
                                    , tipsMore: false
                                    , anim: 5
                                    , fixed: true
                                    , skin: 'layui-box layui-layim-members layui-layim-members-at'
                                    , zIndex: layer.zIndex
                                    , success: function (layero) {
                                        layer.setTop(layero);
                                    }
                                });
                            });
                        }
                    }, 250);
                }
            }
            layer.closeAll('tips');
        },
        pasteImg: function (othis) {
            var thatChat = thisChat();
            thatChat.elem.find('.layim-chat-textarea textarea').bind({
                paste: function (e) {
                    var clipboardData = e.originalEvent.clipboardData,
                        i = 0,
                        items, item, types;
                    if (clipboardData) {
                        items = clipboardData.items;
                        if (!items) {
                            return;
                        }
                        item = items[0];
                        // 保存在剪贴板中的数据类型
                        types = clipboardData.types || [];
                        for (; i < types.length; i++) {
                            if (types[i] === 'Files') {
                                item = items[i];
                                break;
                            }
                        }

                        // 判断是否为图片数据
                        if (item && item.kind === 'file' && item.type.match(/^image\//i)) {
                            events.imgReader(item);
                        }
                    }
                }
            });
        },
        imgReader: function (item) {
            var blob = item.getAsFile();
            var reader = new FileReader();
            reader.readAsDataURL(blob);
            reader.onload = function (event) {
                layer.confirm('<div style="width: 20em;display: flex"><img src="' + event.target.result + '" style="max-width: 100%;margin-bottom: 1.25em"></div><div style="color:#595959;font-size:1.2em;font-weight:500;text-align:center;">确定发送图片吗？</div>', {
                    zIndex: layer.zIndex,
                    offset: ['30%'],
                    success: function (layero) {
                        layer.setTop(layero);
                    }
                }, function (index) {
                    layer.close(index);
                    if (!/\/(?:jpeg|jpg|png|gif)/i.test(blob.type)) {
                        layer.msg('不支持的图片格式', {
                            zIndex: layer.zIndex,
                            success: function (layero) {
                                layer.setTop(layero);
                            }
                        });
                    } else {
                        if (blob.size > 3145728) {
                            layer.msg('图片大小超过3m', {
                                zIndex: layer.zIndex,
                                success: function (layero) {
                                    layer.setTop(layero);
                                }
                            });
                            return;
                        }
                        var formData = new FormData(), thatChat = thisChat();
                        formData.append("file", blob);
                        $.ajax({
                            url: "chat/image",
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (res) {
                                if (res.code == "0") {
                                    focusInsert(thatChat.textarea[0], 'img[' + (res.data.src || '') + ']');
                                    sendMessage(2);
                                }
                            }
                        });
                    }
                });
            }
        },
        shieldMsg: function (othis) {
            layer.closeAll('tips');
            var thatChat = thisChat();
            layer.confirm('屏蔽该消息？', {
                zIndex: layer.zIndex,
                success: function (layero) {
                    layer.setTop(layero);
                }
            }, function (index) {
                layer.close(index);
                var msg_id = othis.parent().data('cid');
                $.post("chat/shield-message", {msg_id: msg_id, room_id: thatChat.data.id}, function (res) {
                    if (res.code !== '200') {
                        parent.layer.msg(res.message, {
                            zIndex: layer.zIndex,
                            success: function (layero) {
                                layer.setTop(layero);
                            }
                        });
                    }
                });
            });
        }
    };

    //暴露接口
    exports('layim', new LAYIM());

// 检查输入
    function check(arg) {
        if (typeof arg == "string") {
            return !!(arg.match(/^\d+((\.\d+){0,})?$/) && parseFloat(arg) > 0)
        } else if (typeof arg == "number") {
            return !isNaN(arg) && arg > 0;
        } else {
            return "参数不正确";
        }
    }
}).addcss(
    'modules/layim/layim.css?v=3.9.1'
    , 'skinlayimcss'
);