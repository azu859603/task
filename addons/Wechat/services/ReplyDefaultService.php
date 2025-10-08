<?php

namespace addons\Wechat\services;

use addons\Wechat\common\models\ReplyDefault;
use common\components\Service;

/**
 * Class ReplyDefaultService
 * @package addons\Wechat\services
 * @author 原创脉冲
 */
class ReplyDefaultService extends Service
{
    /**
     * @return array|ReplyDefault|null|\yii\db\ActiveRecord
     */
    public function findOne()
    {
        if (empty(($model = ReplyDefault::find()->andFilterWhere(['merchant_id' => $this->getMerchantId()])->one()))) {
            return new ReplyDefault();
        }

        return $model;
    }
}