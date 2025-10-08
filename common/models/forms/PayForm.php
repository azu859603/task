<?php

namespace common\models\forms;

use common\helpers\BcHelper;
use common\helpers\CommonPluginHelper;
use common\helpers\GatewayInit;
use common\models\member\RechargeBill;
use common\models\member\RechargeCategory;
use common\models\member\RechargeDetail;
use GatewayClient\Gateway;
use Yii;
use yii\base\Model;
use yii\helpers\Json;
use yii\web\UnprocessableEntityHttpException;
use common\enums\PayGroupEnum;
use common\enums\PayTypeEnum;
use common\models\common\PayLog;
use common\interfaces\PayHandler;
use common\helpers\ArrayHelper;
use common\helpers\StringHelper;

/**
 * 支付校验
 *
 * Class PayForm
 * @package common\models\forms
 * @author 原创脉冲
 */
class PayForm extends PayLog
{
    public $data;

    /**
     * 授权码
     *
     * @var
     */
    public $code;

    /**
     * @var
     */
    private $_handlers;

    public $username;
    public $user_remark;
    public $images;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['order_group', 'pay_type', 'data', 'trade_type', 'member_id'], 'required'],
            [['order_group'], 'in', 'range' => PayGroupEnum::getKeys()],
//            [['pay_type'], 'in', 'range' => PayTypeEnum::getKeys()],
            [['notify_url', 'return_url', 'code', 'openid'], 'string'],
            [['data'], 'safe'],
            [['trade_type'], 'verifyTradeType'],
            [['username'], 'string', 'max' => 100],
            [['user_remark', 'images'], 'string', 'max' => 255],
//            [['pay_type'], 'verifyPayType'],
            [['pay_code'], 'string', 'max' => 50],
        ];
    }

    /**
     * 验证充值类型
     * @param $attribute
     * @param $params
     */
//    public function verifyPayType($attribute, $params)
//    {
//        if (!$this->hasErrors()) {
//            if ($this->order_group == PayGroupEnum::RECHARGE) {
    // 充值
//                if (!RechargeDetail::find()->where(['pid' => $this->pay_type, 'code' => $this->pay_code, 'status' => 1])->exists()) {
//                    return $this->addError($attribute, '该充值类型已关闭，请选择其他类型继续充值！');
//                }
//            }
//            if ($this->pay_type == PayTypeEnum::TRANSFER || $this->pay_type == PayTypeEnum::TRANSFER2 || $this->pay_type == PayTypeEnum::SCAN_CODE || $this->pay_type == PayTypeEnum::WECHAT_SCAN_CODE) {
//                if (empty($this->username)) {
//                    return $this->addError($attribute, '充值人姓名必须填写！');
//                }
//            }
//        }
//    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'images' => '凭证',
        ];
    }

    /**
     * 校验交易类型
     *
     * @param $attribute
     * @throws UnprocessableEntityHttpException
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function verifyTradeType($attribute)
    {
        try {
            $data = $this->data;
            $this->data = Json::decode($data);
        } catch (\Exception $e) {
            $this->addError($attribute, $e->getMessage());
            return;
        }
        switch ($this->pay_type) {
            case PayTypeEnum::WECHAT :
                if (!in_array($this->trade_type, ['native', 'app', 'js', 'pos', 'mweb', 'mini_program'])) {
                    $this->addError($attribute, '微信交易类型不符');

                    return;
                }

                // 直接通过授权码进行支付
                if ($this->code) {
                    if ($this->trade_type == 'mini_program') {
                        $auth = Yii::$app->wechat->miniProgram->auth->session($this->code);
                        Yii::$app->debris->getWechatError($auth);
                        $this->openid = $auth['openid'];
                    }

                    if ($this->trade_type == 'js') {
                        $user = Yii::$app->wechat->app->oauth->user();
                        $this->openid = $user['id'];
                    }
                }

                break;
            case PayTypeEnum::ALI :
                if (!in_array($this->trade_type, ['pc', 'app', 'f2f', 'wap'])) {
                    $this->addError($attribute, '支付宝交易类型不符');
                }
                break;
            case PayTypeEnum::UNION :
                if (!in_array($this->trade_type, ['app', 'html'])) {
                    $this->addError($attribute, '银联交易类型不符');
                }
                break;
        }
    }

    /**
     * 执行类
     *
     * @param array $handlers
     */
    public function setHandlers(array $handlers)
    {
        $this->_handlers = $handlers;
    }

    /**
     * @return array|\EasyWeChat\Kernel\Support\Collection|mixed|object|\Psr\Http\Message\ResponseInterface|string
     * @throws UnprocessableEntityHttpException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \yii\base\InvalidConfigException
     */
    public function getConfig()
    {
        if (!isset($this->_handlers[$this->order_group])) {
            throw new UnprocessableEntityHttpException('找不到订单组别');
        }

        /** @var Model|PayHandler $model */
        $model = new $this->_handlers[$this->order_group]();
        if (!($model instanceof PayHandler)) {
            throw new UnprocessableEntityHttpException('无效的订单组别');
        }

        $model->attributes = $this->data;
        $recharge_detail = RechargeDetail::find()->where(['pid' => $this->pay_type, 'code' => $this->pay_code, 'status' => 1])->asArray()->one();
        if (empty($recharge_detail)) {
            throw new UnprocessableEntityHttpException("该充值方式不可用，请使用其他充值方式");
        }
        $min_recharge_amount = $recharge_detail['min_money'];
        $max_recharge_amount = $recharge_detail['max_money'];
        if ($this->data['money'] < $min_recharge_amount || $this->data['money'] > $max_recharge_amount) {
//            throw new UnprocessableEntityHttpException("充值金额范围是(" . $min_recharge_amount . "-" . $max_recharge_amount . ")元！");
            throw new UnprocessableEntityHttpException("该金额无法进行充值");
        }
        if (!$model->validate()) {
            throw new UnprocessableEntityHttpException(Yii::$app->debris->analyErr($model->getFirstErrors()));
        }

        $orderSn = CommonPluginHelper::getSn($this->member_id);

        $platform_exchange_rate = Yii::$app->debris->backendConfig('platform_exchange_rate');
        $real_recharge_money = BcHelper::mul($model->money, $platform_exchange_rate);
        if ($this->pay_type == 10000 || $this->pay_type == 10001|| $this->pay_type == 10002) {// 线下银行卡/USDT
            if ($this->pay_type == 10000) {
                $real_recharge_money = BcHelper::mul($model->money, $recharge_detail['exchange_rate']);
            }
            RechargeBill::createdModel($orderSn, $model->money, $real_recharge_money, $this->username, $this->pay_type, 0, $this->user_remark, "", $this->images);
            return "true";
        } else {
            RechargeBill::createdModel($orderSn, $model->money, $real_recharge_money, "线上支付", $this->pay_type, 3, "", $this->pay_code);
        }
        $log = new PayLog();
        $log->out_trade_no = $orderSn;
        if ($model->isQueryOrderSn() == true && ($history = Yii::$app->services->pay->findByOrderSn($model->getOrderSn()))) {
            $log = $history;
        }

        $log->attributes = ArrayHelper::toArray($this);
        $log->body = $model->getBody();
        $log->detail = $model->getDetails();
        $log->order_sn = $model->getOrderSn();
        $log->total_fee = $model->getTotalFee();
//        $log->pay_fee = $log->total_fee;
        $log->pay_fee = $real_recharge_money;
        $log->pay_code = $this->pay_code;
        if (!$log->save()) {
            throw new UnprocessableEntityHttpException(Yii::$app->debris->analyErr($log->getFirstErrors()));
        }
        // 写入充值订单

        return $this->payConfig($log);
    }

    /**
     * @return array|\EasyWeChat\Kernel\Support\Collection|mixed|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \yii\base\InvalidConfigException
     */
    protected function payConfig(PayLog $log)
    {
        $recharge_category = RechargeCategory::find()->where(['id' => $log->pay_type])->select(['notify_url'])->asArray()->one();
        $pay = $recharge_category['notify_url'];
        return Yii::$app->services->pay->$pay($log);
    }
}