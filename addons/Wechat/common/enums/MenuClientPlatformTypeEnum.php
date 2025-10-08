<?php

namespace addons\Wechat\common\enums;

use common\enums\BaseEnum;

/**
 * Class MenuClientPlatformTypeEnum
 * @package addons\Wechat\common\enums
 * @author 原创脉冲
 */
class MenuClientPlatformTypeEnum extends BaseEnum
{
    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
            '' => '不限',
            1 => 'IOS(苹果)',
            2 => 'Android(安卓)',
            3 => 'Others(其他)',
        ];
    }
}