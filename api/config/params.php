<?php
return [
    /** ------ 日志记录 ------ **/
    'user.log' => true,
    'user.log.level' => YII_DEBUG ? ['success', 'info', 'warning', 'error'] : ['error'], // 级别 ['success', 'info', 'warning', 'error']
    'user.log.except.code' => [], // 不记录的code

    /** ------ token相关 ------ **/
    // token有效期是否验证 默认不验证
    'user.accessTokenValidity' => false,
    // token有效期 默认 7天
    'user.accessTokenExpire' => 7 * 24 * 60 * 60,
    // refresh token有效期是否验证 默认开启验证
    'user.refreshTokenValidity' => true,
    // refresh token有效期 默认30天
    'user.refreshTokenExpire' => 30 * 24 * 60 * 60,
    // 签名验证默认关闭验证，如果开启需了解签名生成及验证
    'user.httpSignValidity' => true,
    // 签名授权公钥秘钥
    'user.httpSignAccount' => [
        'tea' => 'e3de382b2bab1232s',
    ],
    // 触发格式化返回
    'triggerBeforeSend' => true,
];
