<?php

namespace common\enums;

/**
 * 会员升级类型
 *
 * Class MemberLevelUpgradeTypeEnum
 * @package common\enums
 * @author 原创脉冲
 */
class MemberLevelUpgradeTypeEnum extends BaseEnum
{
    const INTEGRAL = 1;
    const CONSUMPTION_MONEY = 2;
    const EXPERIENCE = 3;

    /**
     * @return array|string[]
     */
    public static function getMap(): array
    {
        return [
            self::INTEGRAL => '积分',
            self::CONSUMPTION_MONEY => '金额',
            self::EXPERIENCE => '经验',
        ];
    }
}