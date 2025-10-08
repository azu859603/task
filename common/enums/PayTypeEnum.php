<?php

namespace common\enums;

/**
 * 支付类型
 *
 * Class PayTypeEnum
 * @package common\enums
 * @author 原创脉冲
 */
class PayTypeEnum extends BaseEnum
{
    const ON_LINE = 0;
    const WECHAT = 1;
    const ALI = 2;
    const UNION = 3;
    const PAY_ON_DELIVERY = 4;
    const USER_MONEY = 5;
    const TO_SHOP = 6;

    // 其他
    const OFFLINE = 100;
    const INTEGRAL = 101;
    const BARGAIN = 102;

    const SCAN_CODE = 10;
    const TRANSFER = 11;
    const TRANSFER2 = 13;
    const WECHAT_SCAN_CODE = 12;
    const BUSINESS = 14;
    const ALIPAY_TRANSFER = 15;
    const ALIPAY_ONLINE_TRANSFER = 16;
    const TRANSFER3 = 17;
    const USDT_TRC20 = 18;

    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
            self::ON_LINE => '在线支付',
            self::WECHAT => '微信',
            self::ALI => '支付宝',
            self::UNION => '银联卡',
            self::PAY_ON_DELIVERY => '货到付款',
            self::USER_MONEY => '余额支付',
            self::TO_SHOP => '到店支付',

            self::OFFLINE => '线下支付',
            self::INTEGRAL => '积分兑换',
            self::BARGAIN => '砍价',

            self::SCAN_CODE => '支付宝扫码',
            self::TRANSFER => '转账银行卡1',
            self::TRANSFER2 => '转账银行卡2',
            self::TRANSFER3 => '转账银行卡3',
            self::WECHAT_SCAN_CODE => '微信扫码',
            self::BUSINESS => '对公转账',
            self::ALIPAY_TRANSFER => '支付宝转账',
            self::ALIPAY_ONLINE_TRANSFER => '支付宝在线转账',
            self::USDT_TRC20 => 'USDT_TRC20',
        ];
    }

    /**
     * 调用方法
     *
     * @param $type
     * @return mixed|string
     */
    public static function action($type)
    {
        $ations = [
            self::WECHAT => 'wechat',
            self::ALI => 'alipay',
            self::UNION => 'union',
        ];

        return $ations[$type] ?? '';
    }

    /**
     * @return array
     */
    public static function thirdParty()
    {
        return [
            self::WECHAT => '微信',
            self::ALI => '支付宝',
            self::UNION => '银联卡',
        ];
    }
}