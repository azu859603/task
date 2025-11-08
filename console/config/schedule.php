<?php
/**
 * @var \omnilight\scheduling\Schedule $schedule
 */

$path = Yii::getAlias('@runtime') . '/logs/';

/**
 * 清理过期的微信历史消息记录
 *
 * 每天凌晨执行一次
 */
//$filePath = $path . 'msgHistory.log';
//$schedule->command('wechat/msg-history/index')->cron('0 0 * * *')->sendOutputTo($filePath);

/**
 * 定时群发微信消息
 *
 * 每分钟执行一次
 */
//$filePath = $path . 'sendMessage.log';
//$schedule->command('wechat/send-message/index')->cron('* * * * *')->sendOutputTo($filePath);

/**
 * 重启ws
 *
 * 每天凌晨执行一次
 */
//$filePath = $path . 'websocket.log';
//$schedule->command('websocket/stop')->cron('0 0 * * *')->sendOutputTo($filePath);
//$schedule->command('websocket/start')->cron('1 0 * * *')->sendOutputTo($filePath);

/**
 * 定时更新用户信息
 *
 * 每天凌晨执行一次
 */
$filePath = $path . 'updateMember.log';
$schedule->command('member/update-sign-status')->cron('0 0 * * *')->appendOutputTo($filePath);
$schedule->command('member/update-sign-day')->cron('0 0 * * *')->appendOutputTo($filePath);


/**
 * 结算订单
 *
 * 每分钟执行
 */
//$filePath = $path . 'investment.log';
//$schedule->command('investment/index')->cron('* * * * *')->appendOutputTo($filePath);

/**
 * 自动增长项目进度
 *
 * 每15分钟执行
 */
//$filePath = $path . 'increase.log';
//$schedule->command('investment/increase')->cron('*/15 * * * *')->appendOutputTo($filePath);

/**
 * 结算订单
 *
 * 每分钟执行
 */
$filePath = $path . 'orders.log';
$schedule->command('orders/index')->cron('* * * * *')->appendOutputTo($filePath);

/**
 * 每10分钟执行
 */
$filePath = $path . 'add.log';
$schedule->command('orders/add')->cron('*/10 * * * *')->appendOutputTo($filePath);

/**
 *
 * 每10分钟执行
 */
$filePath = $path . 'add_detail.log';
$schedule->command('orders/add-detail')->cron('*/10 * * * *')->appendOutputTo($filePath);

/**
 * 定时更新短剧信息
 *
 * 每天凌晨执行一次
 */
$filePath = $path . 'isNew.log';
$schedule->command('orders/is-new')->cron('0 0 * * *')->appendOutputTo($filePath);

/**
 * 自动发货
 *
 * 每分钟执行
 */
$filePath = $path . 'automaticDelivery.log';
$schedule->command('orders/automatic-delivery')->cron('* * * * *')->appendOutputTo($filePath);

/**
 * 自动退货
 *
 * 每分钟执行
 */
$filePath = $path . 'returnGoods.log';
$schedule->command('orders/return-goods')->cron('* * * * *')->appendOutputTo($filePath);


/**
 *
 * 每分钟执行
 */
$filePath = $path . 'promotion.log';
$schedule->command('orders/promotion')->cron('* * * * *')->appendOutputTo($filePath);

/**
 *
 * 每分钟执行
 */
$filePath = $path . 'preSaleReturn.log';
$schedule->command('orders/pre-sale-return')->cron('* * * * *')->appendOutputTo($filePath);