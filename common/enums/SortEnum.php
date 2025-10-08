<?php

namespace common\enums;

/**
 * Class SortEnum
 * @package common\enums
 * @author 原创脉冲
 */
class SortEnum extends BaseEnum
{
    const DESC = 'desc';
    const ASC = 'asc';

    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
            self::DESC => '降序',
            self::ASC => '升序',
        ];
    }
}