<?php

namespace common\traits;

use common\helpers\CommonPluginHelper;
use common\helpers\RedisHelper;
use common\models\common\Languages;
use common\models\dj\LaberList;
use common\models\dj\LaberListTranslations;
use common\models\dj\SellerAvailableList;
use common\models\dj\ShortPlaysDetail;
use common\models\dj\ShortPlaysDetailTranslations;
use common\models\dj\ShortPlaysList;
use common\models\dj\ShortPlaysListTranslations;
use common\models\member\RechargeCategory;
use common\models\member\WithdrawBill;
use Yii;
use yii\helpers\Json;
use yii\web\UnprocessableEntityHttpException;
use common\enums\StatusEnum;
use common\helpers\ArrayHelper;
use common\helpers\FileHelper;
use common\helpers\WechatHelper;
use common\models\common\PayLog;
use common\enums\PayTypeEnum;

/**
 * 支付回调
 *
 * Trait PayNotify
 * @package common\traits
 */
trait PayNotify
{
    /**
     * EasyWechat支付回调 - 微信
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \EasyWeChat\Kernel\Exceptions\Exception
     */
    public function actionEasyWechat()
    {
        $response = Yii::$app->wechat->payment->handlePaidNotify(function ($message, $fail) {
            // 记录写入文件日志
            $logPath = $this->getLogPath('wechat');
            FileHelper::writeLog($logPath, Json::encode(ArrayHelper::toArray($message)));

            /////////////  建议在这里调用微信的【订单查询】接口查一下该笔订单的情况，确认是已经支付 /////////////

            // return_code 表示通信状态，不代表支付状态
            if ($message['return_code'] === 'SUCCESS') {
                if ($this->pay($message)) {
                    return true;
                }
            }

            return $fail('处理失败，请稍后再通知我');
        });

        return $response;
    }

    /**
     * EasyWechat支付回调 - 小程序
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \EasyWeChat\Kernel\Exceptions\Exception
     */
    public function actionWechatMp()
    {
        // 微信支付参数配置
        Yii::$app->params['wechatPaymentConfig'] = ArrayHelper::merge(Yii::$app->params['wechatPaymentConfig'],
            ['app_id' => Yii::$app->debris->backendConfig('miniprogram_appid')]
        );

        $response = Yii::$app->wechat->payment->handlePaidNotify(function ($message, $fail) {
            $logPath = $this->getLogPath('miniprogram');
            FileHelper::writeLog($logPath, Json::encode(ArrayHelper::toArray($message)));

            if ($message['return_code'] === 'SUCCESS') {
                if ($this->pay($message)) {
                    return true;
                }
            }

            return $fail('处理失败，请稍后再通知我');
        });

        return $response;
    }

    /**
     * 公用支付回调 - 支付宝
     *
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionAlipay()
    {
        $request = Yii::$app->pay->alipay([
            'ali_public_key' => Yii::$app->debris->backendConfig('alipay_notification_cert_path'),
        ])->notify();

        try {
            /** @var \Omnipay\Alipay\Responses\AopCompletePurchaseResponse $response */
            $response = $request->send();
            if ($response->isPaid()) {
                $message = Yii::$app->request->post();
                $message['pay_fee'] = $message['total_amount'];
                $message['transaction_id'] = $message['trade_no'];
                $message['mch_id'] = $message['auth_app_id'];

                // 日志记录
                $logPath = $this->getLogPath('alipay');
                FileHelper::writeLog($logPath, Json::encode(ArrayHelper::toArray($message)));

                if ($this->pay($message)) {
                    die('success');
                }
            }

            die('fail');
        } catch (\Exception $e) {
            // 记录报错日志
            $logPath = $this->getLogPath('error');
            FileHelper::writeLog($logPath, $e->getMessage());
            die('fail'); // 通知响应
        }
    }

    /**
     * 公用支付回调 - 微信
     *
     * @return bool|string
     */
    public function actionWechat()
    {
        $response = Yii::$app->pay->wechat->notify();
        if ($response->isPaid()) {
            $message = $response->getRequestData();
            $logPath = $this->getLogPath('wechat');
            FileHelper::writeLog($logPath, Json::encode(ArrayHelper::toArray($message)));

            //pay success 注意微信会发二次消息过来 需要判断是通知还是回调
            if ($this->pay($message)) {
                return WechatHelper::success();
            }

            return WechatHelper::fail();
        } else {
            return WechatHelper::fail();
        }
    }

    /**
     * 公用支付回调 - 银联
     */
    public function actionUnion()
    {
        $response = Yii::$app->pay->union->notify();
        if ($response->isPaid()) {
            //pay success
        } else {
            //pay fail
        }
    }

    /**
     * @param $message
     * @return bool
     */
    protected function pay($message)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!($payLog = Yii::$app->services->pay->findByOutTradeNo($message['out_trade_no']))) {
                throw new UnprocessableEntityHttpException('找不到支付信息');
            };

            // 支付完成
            if ($payLog->pay_status == StatusEnum::ENABLED) {
                return true;
            };

            unset($message['trade_type']);
            $payLog->attributes = $message;
            $payLog->pay_type == PayTypeEnum::WECHAT && $payLog->total_fee = $payLog->total_fee / 100;
            $payLog->pay_status = StatusEnum::ENABLED;
            $payLog->pay_time = time();
            if (!$payLog->save()) {
                throw new UnprocessableEntityHttpException('日志修改失败');
            }

            // 业务回调
            $this->notify($payLog);

            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();

            // 记录报错日志
            $logPath = $this->getLogPath('error');
            FileHelper::writeLog($logPath, $e->getMessage());
            return false;
        }
    }

    /**
     * 支付回调
     *
     * @param PayLog $payLog
     * @throws \yii\web\NotFoundHttpException
     */
    public function notify(PayLog $payLog)
    {
        Yii::$app->services->pay->notify($payLog);
    }

    /**
     * @param $type
     * @return string
     */
    protected function getLogPath($type)
    {
        return Yii::getAlias('@runtime') . "/pay-logs/" . date('Y_m_d') . '/' . $type . '.txt';
    }


    public function actionVideo()
    {
        $action = $this->action->id;
        $data = json_decode(file_get_contents('php://input'), true);
        $logPath = $this->getLogPath($action);
        FileHelper::writeLog($logPath, Json::encode(ArrayHelper::toArray($data)));
        exit("wc");
        if (!empty($data['movie']['episodes'])) {
            $shortPlaysList = ShortPlaysList::find()
                ->where(['vid' => $data['movie']['_id']])
                ->one();
            if (empty($shortPlaysList)) {
                $shortPlaysList = new ShortPlaysList();
                $shortPlaysList->vid = $data['movie']['_id'];
                // 先添加标签
                $lables_data = [];
                foreach ($data['movie']['tags'] as $tag) {
                    $laberListTranslations = LaberListTranslations::find()->where(['title' => $tag])->one();
                    if (empty($laberListTranslations)) {
                        $laberList = new LaberList();
                        $laberList->save();
                        $laberListTranslations = new LaberListTranslations();
                        $laberListTranslations->pid = $laberList->id;
                        $laberListTranslations->title = $tag;
                        $laberListTranslations->lang = "cn";
                        $laberListTranslations->save();
                        $label_id = $laberList->id;
                    } else {
                        $label_id = $laberListTranslations->pid;
                    }
                    $lables_data[] = "$label_id";
                }
                $shortPlaysList->label = $lables_data;
                // 添加剧集
                $shortPlaysList->created_by = 1;
                $shortPlaysList->amount = 20;
                $shortPlaysList->is_new = 0;
                $shortPlaysList->sort = 0;
                $shortPlaysList->aka = $data['movie']['aka'];
                $shortPlaysList->save();
                $shortPlaysListTranslations = ShortPlaysListTranslations::find()->where(['pid' => $shortPlaysList->id, 'title' => $data['movie']['title']])->one();
                if (empty($shortPlaysListTranslations)) {
                    foreach (Languages::find()->select(['code'])->column() as $v1) {
                        $shortPlaysListTranslations = new ShortPlaysListTranslations();
                        $shortPlaysListTranslations->pid = $shortPlaysList->id;
                        $shortPlaysListTranslations->banner = $data['movie']['poster2']['url'];
                        $shortPlaysListTranslations->lang = $v1;
                        if ($v1 == "en" || $v1 == "cn") {
                            $shortPlaysListTranslations->title = $data['movie']['title'];
                        }
                        $shortPlaysListTranslations->save();
                    }
                }
                // 添加上架
                $sellerAvailableList = SellerAvailableList::find()->where(['member_id' => 1, 'pid' => $shortPlaysList->id])->one();
                if (empty($sellerAvailableList)) {
                    $sellerAvailableList = new SellerAvailableList();
                    $sellerAvailableList->member_id = 1;
                    $sellerAvailableList->pid = $shortPlaysList->id;
                    $sellerAvailableList->save();
                }
            }
            foreach ($data['movie']['episodes'] as $k => $v) {
                $shortPlaysDetail = ShortPlaysDetail::find()->where(['vid' => $v['movieid']])->one();
                if (empty($shortPlaysDetail)) {
                    $shortPlaysDetail = new ShortPlaysDetail();
                    $shortPlaysDetail->pid = $shortPlaysList->id;
                    $shortPlaysDetail->vid = $v['movieid'];
                    $shortPlaysDetail->number = $v['episode'];
                    if ($k == 0 || $k == 1 || $k == 2) {
                        $shortPlaysDetail->type = 0;
                    } else {
                        $shortPlaysDetail->type = 1;
                    }
                    $shortPlaysDetail->save();
                }

//                foreach (Languages::find()->select(['code'])->column() as $v1) {
//                    if (!ShortPlaysDetailTranslations::find()->where(['pid' => $shortPlaysDetail->id, 'lang' => $v1])->exists()) {
//                        $shortPlaysDetailTranslations = new ShortPlaysDetailTranslations();
//                        $shortPlaysDetailTranslations->pid = $shortPlaysDetail->id;
//                        $shortPlaysDetailTranslations->title = $v['episode'];
//                        $shortPlaysDetailTranslations->content = "/movie/auto/" . $v['movieid'] . ".m3u8";
//                        $shortPlaysDetailTranslations->banner = $data['movie']['poster2']['url'];
//                        $shortPlaysDetailTranslations->lang = $v1;
//                        $shortPlaysDetailTranslations->save();
//                    }
//                }

                if (!ShortPlaysDetailTranslations::find()->where(['pid' => $shortPlaysDetail->id, 'lang' => 'cn'])->exists()) {
                    $shortPlaysDetailTranslations = new ShortPlaysDetailTranslations();
                    $shortPlaysDetailTranslations->pid = $shortPlaysDetail->id;
                    $shortPlaysDetailTranslations->title = $v['episode'];
                    $shortPlaysDetailTranslations->content = "/movie/auto/" . $v['movieid'] . ".m3u8";
                    $shortPlaysDetailTranslations->banner = $data['movie']['poster2']['url'];
                    $shortPlaysDetailTranslations->lang = 'cn';
                    $shortPlaysDetailTranslations->save();
                }


            }
        }
        var_dump("完成！");
        exit;
    }



    //XfPay代付
    public function actionXfPay()
    {
        $register_ip = Yii::$app->request->getUserIP();
        RedisHelper::verify($register_ip, $this->action->id);
        $action = $this->action->id;
        $data = Yii::$app->request->post();
        $logPath = $this->getLogPath($action);
        FileHelper::writeLog($logPath, Json::encode(ArrayHelper::toArray($data)));
        $sign = $data['sign'];
        unset($data['sign']);
        $key = "BE47EFB5CE0A522AF36286BE6FB03B67";
        $sign_my = CommonPluginHelper::xfpay_sign($key, $data);
        if ($sign != $sign_my) {
            die('fail');
        }
        if (!empty($data) && !empty($data['status'])) {
            // 成功
            $model = WithdrawBill::find()->where(['sn' => $data['orderid'], 'status' => 4])->one();
            if (empty($model)) {
                die('fail');
            }
            if ($data['status'] == 1) { // 成功
                $model->status = 1;
            } else { // 错误直接拒绝
                $model->status = 2;
            }
            $model->updated_at = time();
            $model->save(false);
            die('OK');
        } else {
            die('fail');
        }
    }
}