<?php

namespace services\common;

use Yii;
use common\enums\AppEnum;
use common\components\Service;

/**
 * Class AuthService
 * @package services\common
 * @author 原创脉冲
 */
class AuthService extends Service
{
    /**
     * 是否超级管理员
     *
     * @return bool
     */
    public function isSuperAdmin()
    {
        if (!in_array(Yii::$app->id, [AppEnum::BACKEND, AppEnum::MERCHANT])) {
            return false;
        }

        return Yii::$app->user->id == Yii::$app->params['adminAccount'];
    }
}