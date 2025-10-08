<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'api',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'api\controllers',
    'bootstrap' => ['log'],
    'modules' => [
        'v1' => [ // 版本1
            'class' => 'api\modules\v1\Module',
        ],
        'v2' => [ // 版本2
            'class' => 'api\modules\v2\Module',
        ],
    ],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-api',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
                'text/json' => 'yii\web\JsonParser',
            ]
        ],
        'response' => [
            'class' => 'yii\web\Response',
            'as beforeSend' => 'api\behaviors\BeforeSend',
        ],
        'user' => [
            'identityClass' => 'common\models\api\AccessToken',
            'enableAutoLogin' => true,
            'enableSession' => false,// 显示一个HTTP 403 错误而不是跳转到登录界面
            'loginUrl' => null,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error'],
                    'logFile' => '@runtime/logs/' . date('Y-m/d') . '.log',
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'message/error',
        ],
        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            // 美化Url,默认不启用。但实际使用中，特别是产品环境，一般都会启用。
            'enablePrettyUrl' => true,
            // 是否启用严格解析，如启用严格解析，要求当前请求应至少匹配1个路由规则，
            // 否则认为是无效路由。
            // 这个选项仅在 enablePrettyUrl 启用后才有效。启用容易出错
            // 注意:如果不需要严格解析路由请直接删除或注释此行代码
            'enableStrictParsing' => true,
            // 是否在URL中显示入口脚本。是对美化功能的进一步补充。
            'showScriptName' => false,
            // 指定续接在URL后面的一个后缀，如 .html 之类的。仅在 enablePrettyUrl 启用时有效。
            'suffix' => '',
            'rules' => [
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => [
                        /**
                         * 默认登录测试控制器(Post)
                         * http://当前域名/api/v1/site/login
                         */
                        // 'sign-secret-key',
                        // 版本1
                        'v1/default',// 默认测试入口
                        'v1/site',
                        'v1/mini-program',
                        'v1/pay',
                        'v1/common/provinces',
                        'v1/member/member',
                        'v1/member/address',
                        'v1/member/invoice',
                        'v1/member/auth',
                        'v1/member/bank-account',
                        'v1/test',
                        'v1/member/credits-log',
                        'v1/member/recharge-bill',
                        'v1/member/withdraw-bill',
                        'v1/common/article-category',
                        'v1/common/article-details',
                        'v1/common/img-details',
                        'v1/common/config',
                        'v1/common/opinion-list',// 意见反馈
                        'v1/member/sign',
                        'v1/member/account',
                        'v1/common/lottery', // 摇奖
                        'v1/tea/investment-project', // 项目列表
                        'v1/tea/investment-bill', // 订单列表
                        'v1/tea/sign-goods-list', // 签到商品列表
                        'v1/tea/sign-goods-bill', // 签到订单列表
                        'v1/tea/category-list', // 分类列表
                        'v1/tea/activity', // 活动列表
                        'v1/tea/activity-card-list', // 卡片列表
                        'v1/member/notify', // 站内消息
                        'v1/member/card', // 银行卡
                        'v1/member/red-envelope', // 红包
                        'v1/tea/questions', // 碳问答
                        'v1/common/web-list', // 碳问答
                        'v1/tea/coupon-member', // 优惠券
                        'v1/tea/project',
                        'v1/tea/bill',
                        'v1/tea/detail',
                        'v1/tea/chat',
                        'v1/member/realname-audit', // 实名认证
                        'v1/dj/buy-level', // 购买等级
                        'v1/dj/seller-available-list', // 上架短剧
                        'v1/common/languages', // 语言列表
                        'v1/dj/orders', // 订单
                        'v1/dj/short-plays-list', // 短剧列表
                        'v1/dj/short-plays-list-buyer', // 买家
                        'v1/dj/short-plays-list-seller', // 卖家
                        'v1/dj/chat', // 卖家
                        'v1/dj/promotion', // 推广
                        // 版本2
                        'v2/default', // 默认测试入口
                    ],
                    'pluralize' => false, // 是否启用复数形式，注意index的复数indices，开启后不直观
                    'extraPatterns' => [
                        'POST login' => 'login', // 登录获取token
                        'POST mobile-login' => 'mobile-login', // 手机登录获取token
                        'POST logout' => 'logout', // 退出登录
                        'POST refresh' => 'refresh', // 重置token
                        'POST sms-code' => 'sms-code', // 获取验证码
                        'POST register' => 'register', // 注册
                        'POST register-anonymous' => 'register-anonymous', // 匿名注册
                        'POST up-pwd' => 'up-pwd', // 重置密码
                        // 测试查询可删除 例如：http://www.rageframe.com/api/v1/default/search
                        'GET search' => 'search',
                        'GET qr-code' => 'qr-code', // 获取小程序码
                        'GET list' => 'list', // 列表
                        'GET detail' => 'detail', // 详情
                        'POST update-password' => 'update-password', //修改密码
                        'POST update-member' => 'update-member', //修改个人资料
                        'POST update-safety-password' => 'update-safety-password', //修改安全密码
                        'POST verify-safety-password' => 'verify-safety-password', //验证安全密码
                        'GET captcha' => 'captcha', //验证码
                        'POST over' => 'over', //收货
                        'POST binding' => 'binding', //绑定
                        'POST open' => 'open', //开奖
                        'POST send' => 'send', //发送卡片
                        'POST read' => 'read', //已读
                        'POST unbind' => 'unbind', //解绑
                        'POST exchange' => 'exchange', //兑换
                        'GET chat-history' => 'chat-history',
                        'POST read-private-msg' => 'read-private-msg',
                        'POST send-msg' => 'send-msg',
                        'GET reconnect' => 'reconnect',
                        'POST send-image' => 'send-image',
                        'GET notify' => 'notify',
                        'GET hot' => 'hot',
                        'GET laber-list' => 'laber-list',
                        'POST down' => 'down',
                        'GET recently-viewed' => 'recently-viewed',
                        'POST add-recently-viewed' => 'add-recently-viewed',
                        'GET like-list' => 'like-list',
                        'POST add-like-list' => 'add-like-list',
                        'POST un-like-list' => 'un-like-list',
                        'POST seller-shipping' => 'seller-shipping',
                        'GET seller-order-list' => 'seller-order-list',
                        'GET hot-list' => 'hot-list',
                        'POST return-goods' => 'return-goods',
                        'GET count' => 'count',
                        'POST add-collect-list' => 'add-collect-list',
                        'POST un-collect-list' => 'un-collect-list',
                        'GET collect-list' => 'collect-list',
                        'GET team' => 'team',
                        'POST seller-shipping-all' => 'seller-shipping-all',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['v1/file'],
                    'pluralize' => false,
                    'extraPatterns' => [
                        'POST images' => 'images', // 图片上传
                        'POST videos' => 'videos', // 视频上传
                        'POST voices' => 'voices', // 语音上传
                        'POST files' => 'files', // 文件上传
                        'POST base64' => 'base64', // base64上传
                        'POST merge' => 'merge', // 合并分片
                        'POST verify-md5' => 'verify-md5', // md5文件校验
                        'GET oss-accredit' => 'oss-accredit', // oss js 直传配置
                    ],
                ],
                [
                    'class' => 'api\rest\UrlRule',
                    'controller' => ['addons'],
                    'pluralize' => false,
                ],
            ]
        ],
    ],
    'as cors' => [
        'class' => \yii\filters\Cors::class,
    ],
    'params' => $params,
];
