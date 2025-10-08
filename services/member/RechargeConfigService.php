<?php

namespace services\member;

use common\components\Service;
use common\enums\StatusEnum;
use common\models\member\RechargeConfig;

/**
 * Class RechargeConfigService
 * @package services\member
 * @author 原创脉冲
 */
class RechargeConfigService extends Service
{
    /**
     * 获取赠送金额
     *
     * @param $money
     * @return int
     */
    public function getGiveMoney($money)
    {
        $model = RechargeConfig::find()
            ->select(['give_price'])
            ->where(['<=', 'price', $money])
            ->andWhere(['status' => StatusEnum::ENABLED])
            ->orderBy('price desc')
            ->asArray()
            ->one();

        return !empty($model['give_price']) ? $model['give_price'] : 0;
    }
}