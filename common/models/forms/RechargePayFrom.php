<?php

namespace common\models\forms;

use yii\base\Model;
use common\helpers\StringHelper;
use common\interfaces\PayHandler;
use Yii;
/**
 * Class RechargePayFrom
 * @package common\models\forms
 * @author 原创脉冲
 */
class RechargePayFrom extends Model implements PayHandler
{
    /**
     * @var
     */
    public $money;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['money', 'required'],
            [['money'], 'number', 'min' => Yii::$app->debris->config('minimum_recharge_amount')],
            [['money'], 'number', 'numberPattern' => '/^\d+(.\d{1,2})?$/', 'message' => '小数点后位数不能大于俩位'],
        ];
    }


    public function attributeLabels()
    {
        return [
            'money' => '金额',
        ];
    }

    /**
     * 支付说明
     *
     * @return string
     */
    public function getBody(): string
    {
        return '在线充值';
    }

    /**
     * 支付详情
     *
     * @return string
     */
    public function getDetails(): string
    {
        return '';
    }

    /**
     * 支付金额
     *
     * @return float
     */
    public function getTotalFee(): float
    {
        return $this->money;
    }

    /**
     * 获取订单号
     *
     * @return float
     */
    public function getOrderSn(): string
    {
        return time() . StringHelper::random(8, true);
    }

    /**
     * 是否查询订单号(避免重复生成)
     *
     * @return bool
     */
    public function isQueryOrderSn(): bool
    {
        return false;
    }
}