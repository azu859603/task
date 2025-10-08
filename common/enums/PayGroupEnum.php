<?php

namespace common\enums;

/**
 * 支付组别
 *
 * Class PayGroupEnum
 * @package common\enums
 * @author 原创脉冲
 */
class PayGroupEnum extends BaseEnum
{
    const ORDER = 'order';
    const RECHARGE = 'recharge';

    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
            self::ORDER => '订单',
            self::RECHARGE => '充值',
        ];
    }
}