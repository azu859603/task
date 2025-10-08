<?php

namespace api\modules\v1\controllers;

use common\enums\PayGroupEnum;
use common\helpers\RedisHelper;
use common\models\member\Member;
use common\models\member\RechargeCategory;
use Yii;
use api\controllers\OnAuthController;
use common\enums\PayTypeEnum;
use common\helpers\Url;
use common\models\forms\PayForm;
use common\helpers\ResultHelper;
use common\models\forms\OrderPayFrom;
use common\models\forms\RechargePayFrom;
use yii\helpers\Json;

/**
 * 公用支付生成
 *
 * Class PayController
 * @package api\modules\v1\controllers
 * @author 原创脉冲
 */
class PayController extends OnAuthController
{
    /**
     * @var PayForm
     */
    public $modelClass = PayForm::class;

    /**
     * 生成支付参数
     *
     * @return array|bool|mixed|\yii\db\ActiveRecord
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionCreate()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        if (intval(Yii::$app->debris->config('recharge_switch')) === 0) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "充值功能暂未开启");
        }
        // 判断是否实名制
//        $memberInfo = Member::find()->where(['id' => $this->memberId])->one();
//        if (empty($memberInfo->realname)) {
//            return ResultHelper::json(ResultHelper::ERROR_CODE, '请您先实名认证后再继续操作');
//        }
        /* @var $payForm PayForm */
        $payForm = new $this->modelClass();
        $payForm->attributes = Yii::$app->request->post();
        $payForm->member_id = $this->memberId;
        $payForm->code = Yii::$app->request->get('code');
        // 验证
        if (!$payForm->validate()) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, $this->getError($payForm));
        }

        // 非余额支付
        if ($payForm->pay_type != PayTypeEnum::USER_MONEY) {

            // 执行方法
            $payForm->setHandlers([
                'recharge' => RechargePayFrom::class,
                'order' => OrderPayFrom::class,
            ]);
            // 回调方法
//            $payForm->notify_url = Url::removeMerchantIdUrl('toFront', ['notify/' . PayTypeEnum::action($payForm->pay_type)]);
            $category = RechargeCategory::find()->where(['id' => $payForm->pay_type])->select(['notify_url'])->asArray()->one();
            $payForm->notify_url = Url::removeMerchantIdUrl('toFront', ['notify/' . $category['notify_url']]);
            !$payForm->openid && $payForm->openid = Yii::$app->user->identity->openid;

            // 生成配置
            return ResultHelper::json(200, '充值申请已成功，请等待审核', [
                'payStatus' => false,
                'config' => $payForm->getConfig(),
            ]);
        }
    }
}