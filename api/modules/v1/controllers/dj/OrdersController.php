<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2019/11/24
 * Time: 2:08
 */

namespace api\modules\v1\controllers\dj;

use api\controllers\OnAuthController;
use api\modules\v1\forms\common\ImgForm;
use common\enums\StatusEnum;
use common\helpers\BcHelper;
use common\helpers\CommonPluginHelper;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\common\Languages;
use common\models\common\Statistics;
use common\models\dj\Orders;
use common\models\dj\PromotionDetail;
use common\models\dj\PromotionOrder;
use common\models\dj\SellerAvailableList;
use common\models\dj\SellerAvailableOrder;
use common\models\dj\ShortPlaysDetail;
use common\models\dj\ShortPlaysDetailTranslations;
use common\models\dj\ShortPlaysList;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\UnprocessableEntityHttpException;

class OrdersController extends OnAuthController
{
    public $modelClass = Orders::class;

//    // 不用进行签名验证的方法
//    protected $signOptional = ['detail'];
//
//    // 不用进行登录验证的方法
//    protected $authOptional = ['detail'];

    /**
     * 买家解锁短剧
     * @return array|mixed
     */
    public function actionOpen()
    {
        $private_key = Yii::$app->request->post('private_key');
        $order = Orders::find()->where(['private_key' => $private_key])->one();
        if (empty($order)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '信息错误');
        }
        $order->key_status = 1;
        $order->save();
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, '解锁成功');
    }


    /**
     * 买家购买记录
     * @return ActiveDataProvider
     */
    public function actionIndex()
    {
        $keyword = Yii::$app->request->get('keyword');
        $key_status = Yii::$app->request->get('key_status');
        if (isset($key_status)) {
            $keyStatusWhere = ['key_status' => $key_status];
        } else {
            $keyStatusWhere = [];
        }
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        return new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->where(['dj_orders.member_id' => $this->memberId])
                ->andWhere(['<', 'dj_orders.created_at', time()])
                ->andFilterWhere($keyStatusWhere)
                ->orderBy('dj_orders.created_at desc')
                ->with([
                    'shortPlaysList' => function ($query) use ($lang, $keyword) {
                        $query->with([
                            'translation' => function ($query) use ($lang, $keyword) {
                                $query->where(['lang' => $lang]);
                                if ($keyword) {
                                    $query->where(['like', 'title', "%" . $keyword . "%", false]);
                                }
                            },
                        ])->with([
                            'shortPlaysDetails' => function ($query) {
                                $query->select(['id', 'pid'])->where(['type' => 1]);
                            }
                        ]);
                    },
                ])
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
    }

    /**
     * 买家下单
     * @return array|mixed|\yii\db\ActiveRecord
     * @throws UnprocessableEntityHttpException
     */
    public function actionCreate()
    {
        RedisHelper::verify($this->memberId, $this->action->id);

        $memberInfo = Member::find()->where(['id' => $this->memberId])->with(['sellerLevel'])->one();
        $pid = Yii::$app->request->post('id');

        $transaction = Yii::$app->db->beginTransaction();
        try {

            $shortPlaysList = ShortPlaysList::find()
                ->where(['id' => $pid])
                ->with([
                    'sellerAvailableList' => function ($query) use ($memberInfo) {
                        $query->where(['member_id' => $memberInfo['pid']]);
                    }
                ])
                ->one();

            if (Orders::find()->where(['pid' => $pid, 'member_id' => $this->memberId])->exists()) {
                throw new UnprocessableEntityHttpException('该短剧您已经购买过');
            }


            $member_sql = "select * from rf_member_account where `member_id`={$this->memberId} and `user_money` >= {$shortPlaysList->amount} for update";
            $member_lock = Yii::$app->db->createCommand($member_sql)->queryOne();
            if (empty($member_lock)) {
                throw new UnprocessableEntityHttpException('您的余额不足，请及时充值');
            }

            // 如果购买人的上级卖家未上架短剧
//            if (!empty($shortPlaysList['sellerAvailableList']) && $shortPlaysList['sellerAvailableList']['status'] == 1) {
//                // 上架了的
//                $seller_id = $memberInfo['pid'];
//                $income = BcHelper::mul(BcHelper::div($memberInfo->sellerLevel->profit, 100, 4), $shortPlaysList->amount);
//                $dx_money = BcHelper::sub($shortPlaysList->amount, $income);
//            } else {
////                $seller_id = !empty($memberInfo['pid']) ? $memberInfo['pid'] : 1;
//                $seller_id = 1;
//                $income = BcHelper::mul(BcHelper::div($memberInfo->sellerLevel->profit_rebate, 100, 4), $shortPlaysList->amount);
//                $dx_money = BcHelper::sub($shortPlaysList->amount, $income);
//
//            }

            // 如果是购买了上架短剧
            if (!empty($shortPlaysList['sellerAvailableList']) && $shortPlaysList['sellerAvailableList']['status'] == 1) {
                $seller_id = $memberInfo['pid'];
                // 代销价格从预售订单计算
                $sellerAvailableOrder = SellerAvailableOrder::find()
                    ->where(['member_id' => $seller_id, 'pid' => $pid])
                    ->andWhere(['>', 'buy_number', 0])
                    ->orderBy(['created_at' => SORT_ASC])
                    ->one();
                $dx_money = BcHelper::div($sellerAvailableOrder['buy_money'],$sellerAvailableOrder['buy_number']);
                $income = BcHelper::sub($shortPlaysList->amount, $dx_money);
            }else{
                $seller_id = 1;
                $income = BcHelper::mul(BcHelper::div($memberInfo->sellerLevel->profit_rebate, 100, 4), $shortPlaysList->amount);
                $dx_money = BcHelper::sub($shortPlaysList->amount, $income);
            }

            $member = Member::find()->where(['id' => $member_lock['member_id']])->with(['account'])->one();
            $order = new Orders();
            $order->seller_id = $seller_id;
            $order->pid = $pid;
            $order->money = $shortPlaysList->amount;
            $order->income = $income;
            $order->dx_money = $dx_money;
            $order->private_key = md5($pid . time());
            $order->member_id = $this->memberId;
            $time = time();
            $order->created_at = $time;
            // 扣除买家余额
            Yii::$app->services->memberCreditsLog->decrMoney(new CreditsLogForm([
                'member' => $member,
                'time' => $time,
                'pay_type' => CreditsLog::BUY_SHORT_PLAYS_TYPE,
                'num' => $order->money,
                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                'remark' => "【短剧】购买短剧扣除余额",
            ]));

            // 销售量+1
            $shortPlaysList->buy_number += 1;
            $shortPlaysList->save();

            // 新订单站内信
            Yii::$app->services->memberNotify->createMessage("新的订单", "您有新的订单，请前往订单列表查看", 1, [$order->seller_id], time());


            if ($order['seller_id'] != 1) {
                $sellerAvailableOrder->buy_number -= 1;
                $sellerAvailableOrder->buy_money = BcHelper::sub($sellerAvailableOrder->buy_money, $dx_money);
                $sellerAvailableOrder->save();
                // 判断短剧是否该下架
                if (SellerAvailableOrder::find()->where(['member_id' => $order['seller_id'], 'pid' => $order['pid']])->sum('buy_number') == 0) {
                    $sellerAvailableList = SellerAvailableList::find()->where(['member_id' => $order['seller_id'], 'pid' => $order['pid']])->one();
                    if (!empty($sellerAvailableList) && $order['seller_id'] != 1) {
                        $sellerAvailableList->status = 0;
                        $sellerAvailableList->save(false);
                    }
                }
            }

            // 卖家发货
            $seller_member = Member::find()->where(['id' => $order['seller_id']])->with(['sellerLevel', 'account'])->one();
            $order->status = 1;
            $time = time();
            $order->updated_at = $time;
            $order->over_time = BcHelper::add(BcHelper::mul(86400, $seller_member->sellerLevel->return_income_time, 0), $time, 0);

            // 发货成功，发送站内信
            Yii::$app->services->memberNotify->createMessage("发货通知", "您购买的短剧订单已发货，短剧密钥为：" . $order->private_key, 1, [$order->member_id], time());
            // 统计订单总数和销售额
            $seller_member->account->investment_all_money = BcHelper::add($seller_member->account->investment_all_money, $order->money);
            $seller_member->account->investment_number = $seller_member->account->investment_number + 1;
            $seller_member->account->save(false);
            // 用户升级 更新等级
            Yii::$app->services->memberLevel->updateSellerLevel($seller_member);
            // 统计销售额
            // 加入统计表 获取最上级用户ID
            if ($seller_member['type'] == 1) {
                $first_member = Member::getParentsFirst($seller_member);
                $b_id = $first_member['b_id'] ?? 0;
                Statistics::updateBuyMoney(date("Y-m-d"), $order->money, $b_id);
            }
            // 判断卖家是否开启推流
            if ($seller_member->push_flow_switch == 1) {
                $promotion = PromotionOrder::find()->where(['member_id' => $seller_member->id, 'status' => 1])->one();
                if (!empty($promotion)) {
                    $date = date("Y-m-d");
                    // 添加详情
                    $promotion_detail = PromotionDetail::find()->where(['pid' => $promotion->id, 'created_at' => $date])->one();
                    if (empty($promotion_detail)) {
                        $promotion_detail = new PromotionDetail();
                        $promotion_detail->member_id = $promotion->member_id;
                        $promotion_detail->pid = $promotion->id;
                        $promotion_detail->created_at = $date;
                    }
                    $promotion_detail->quantity += 1;
                    // 未结算收益增加
                    $promotion_detail->doing_money = BcHelper::add($promotion_detail->doing_money, $order->income);

                    $order->push_flow_switch = 1;

                    $promotion_detail->save(false);
                }
            }
            $order->save();


            $transaction->commit();
            return ResultHelper::json(ResultHelper::SUCCESS_CODE, "购买成功");
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ResultHelper::json(ResultHelper::ERROR_CODE, $e->getMessage());
        }
    }

    /**
     * 卖家订单列表
     * @return array
     */
    public function actionSellerOrderList()
    {
        $keyword = Yii::$app->request->get('keyword');
        $income_status = Yii::$app->request->get('income_status');
        if (isset($income_status)) {
            $statusWhere = ['dj_orders.income_status' => $income_status];
        } else {
            $statusWhere = [];
        }
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        $models = new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->where(['dj_orders.seller_id' => $this->memberId])
                ->andWhere(['<', 'dj_orders.created_at', time()])
                ->andFilterWhere($statusWhere)
                ->orderBy('dj_orders.created_at desc')
                ->with([
                    'shortPlaysList' => function ($query) use ($lang, $keyword) {
                        $query->with([
                            'translation' => function ($query) use ($lang, $keyword) {
                                $query->where(['lang' => $lang]);
                                if ($keyword) {
                                    $query->where(['like', 'title', "%" . $keyword . "%", false]);
                                }
                            },
                        ])->with(['shortPlaysDetails' => function ($query) {
                            $query->select(['id', 'pid'])->where(['type' => 1]);
                        }]);
                    },
                ])
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
        $models = $models->getModels();
        $member = Member::find()->where(['id' => $this->memberId])->with(['account', 'sellerLevel'])->one();
        foreach ($models as $k => $order) {
            if ($member->push_flow_switch == 1) {
                $push_flow = $member->sellerLevel->push_flow;
                $models[$k]['push_flow_money'] = BcHelper::mul($order['income'], BcHelper::div($push_flow, 100, 4));
            } else {
                $models[$k]['push_flow_money'] = 0;
            }
        }
        return $models;
    }

    /**
     * 卖家发货（一键）
     * @return void
     * @throws UnprocessableEntityHttpException
     */
    public function actionSellerShippingAll()
    {
//        RedisHelper::verify($this->memberId, $this->action->id);
//        $orders = Orders::find()
//            ->where(['status' => 0, 'income_status' => 0])
//            ->all();
//        if (empty($orders)) {
//            return ResultHelper::json(ResultHelper::ERROR_CODE, '当前暂无能发货的订单');
//        }
//        $money = Orders::find()
//                ->where(['status' => 0, 'income_status' => 0])
//                ->sum('dx_money') ?? 0;
//        $transaction = Yii::$app->db->beginTransaction();
//        try {
//            // 卖家发货，支付货款
//            $member_sql = "select * from rf_member_account where `member_id`={$this->memberId} and `user_money` >= {$money} for update";
//            $member_lock = Yii::$app->db->createCommand($member_sql)->queryOne();
//            if (empty($member_lock)) {
//                throw new UnprocessableEntityHttpException('您的余额不足，请及时充值');
//            }
//            $member = Member::find()
//                ->where(['id' => $member_lock['member_id']])
//                ->with([
//                    'account',
//                    'sellerLevel'
//                ])
//                ->one();
//            foreach ($orders as $order) {
//
//
////                // 扣除卖家余额
////                Yii::$app->services->memberCreditsLog->decrMoney(new CreditsLogForm([
////                    'member' => $member,
////                    'pay_type' => CreditsLog::SEND_SHORT_PLAYS_TYPE,
////                    'num' => $order->dx_money,
////                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
////                    'remark' => "【短剧】发货扣除",
////                ]));
//
//
//                $order->status = 1;
//                $time = time();
//                $order->updated_at = $time;
//                $order->over_time = BcHelper::add(BcHelper::mul(86400, $member->sellerLevel->return_income_time, 0), $time, 0);
//
//                // 发货成功，发送站内信
//                Yii::$app->services->memberNotify->createMessage("发货通知", "您购买的短剧订单已发货，短剧密钥为：" . $order->private_key, 1, [$order->member_id], time());
//
//                // 统计订单总数和销售额
//                $member->account->investment_all_money = BcHelper::add($member->account->investment_all_money, $order->money);
//                $member->account->investment_number = $member->account->investment_number + 1;
//                $member->account->save(false);
//
//                // 用户升级 更新等级
//                Yii::$app->services->memberLevel->updateSellerLevel($member);
//
//                // 统计销售额
//                // 加入统计表 获取最上级用户ID
//                if ($member['type'] == 1) {
//                    $first_member = Member::getParentsFirst($member);
//                    $b_id = $first_member['b_id'] ?? 0;
//                    Statistics::updateBuyMoney(date("Y-m-d"), $order->money, $b_id);
//                }
//
//                // 判断卖家是否开启推流
//                if ($member->push_flow_switch == 1) {
//                    $promotion = PromotionOrder::find()->where(['member_id' => $this->memberId, 'status' => 1])->one();
//                    if (!empty($promotion)) {
//                        $date = date("Y-m-d");
//                        // 添加详情
//                        $promotion_detail = PromotionDetail::find()->where(['pid' => $promotion->id, 'created_at' => $date])->one();
//                        if (empty($promotion_detail)) {
//                            $promotion_detail = new PromotionDetail();
//                            $promotion_detail->member_id = $promotion->member_id;
//                            $promotion_detail->pid = $promotion->id;
//                            $promotion_detail->created_at = $date;
//                        }
//                        $promotion_detail->quantity += 1;
//
//                        // 未结算收益增加
//                        $promotion_detail->doing_money = BcHelper::add($promotion_detail->doing_money, $order->income);
//
//                        $order->push_flow_switch = 1;
//                        $promotion_detail->save(false);
//                    }
//                }
//
//                $order->save();
//            }
//
//            $transaction->commit();
//            return ResultHelper::json(ResultHelper::SUCCESS_CODE, "发货成功");
//        } catch (\Exception $e) {
//            $transaction->rollBack();
//            return ResultHelper::json(ResultHelper::ERROR_CODE, $e->getMessage());
//        }
    }


    /**
     * 卖家发货
     * @return void
     * @throws UnprocessableEntityHttpException
     */
    public function actionSellerShipping()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        $id = Yii::$app->request->post('id');
        $order = Orders::find()->where(['id' => $id, 'status' => 0, 'income_status' => 0])->one();
        if (empty($order)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '订单已发货或信息错误');
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 卖家发货，支付货款
            $member_sql = "select * from rf_member_account where `member_id`={$this->memberId} and `user_money` >= {$order->dx_money} for update";
            $member_lock = Yii::$app->db->createCommand($member_sql)->queryOne();
            if (empty($member_lock)) {
                throw new UnprocessableEntityHttpException('您的余额不足，请及时充值');
            }
            $member = Member::find()
                ->where(['id' => $member_lock['member_id']])
                ->with([
                    'account',
                    'sellerLevel'
                ])
                ->one();
            $order->status = 1;
            $time = time();
            $order->updated_at = $time;
            $order->over_time = BcHelper::add(BcHelper::mul(86400, $member->sellerLevel->return_income_time, 0), $time, 0);
            $order->save();
            // 发货成功，发送站内信
            Yii::$app->services->memberNotify->createMessage("发货通知", "您购买的短剧订单已发货，短剧密钥为：" . $order->private_key, 1, [$order->member_id], time());


            // 统计订单总数和销售额
            $member->account->investment_all_money = BcHelper::add($member->account->investment_all_money, $order->money);
            $member->account->investment_number = $member->account->investment_number + 1;
            $member->account->save(false);

            // 用户升级 更新等级
            Yii::$app->services->memberLevel->updateSellerLevel($member);

            // 统计销售额
            // 加入统计表 获取最上级用户ID
            if ($member['type'] == 1) {
                $first_member = Member::getParentsFirst($member);
                $b_id = $first_member['b_id'] ?? 0;
                Statistics::updateBuyMoney(date("Y-m-d"), $order->money, $b_id);
            }


            // 判断卖家是否开启推流
            if ($member->push_flow_switch == 1) {
                $promotion = PromotionOrder::find()->where(['member_id' => $this->memberId, 'status' => 1])->one();
                if (!empty($promotion)) {
                    $date = date("Y-m-d");
                    // 添加详情
                    $promotion_detail = PromotionDetail::find()->where(['pid' => $promotion->id, 'created_at' => $date])->one();
                    if (empty($promotion_detail)) {
                        $promotion_detail = new PromotionDetail();
                        $promotion_detail->member_id = $promotion->member_id;
                        $promotion_detail->pid = $promotion->id;
                        $promotion_detail->created_at = $date;
                    }
                    $promotion_detail->quantity += 1;

                    // 未结算收益增加
                    $promotion_detail->doing_money = BcHelper::add($promotion_detail->doing_money, $order->income);

                    $promotion_detail->save(false);
                }
            }

            $transaction->commit();
            return ResultHelper::json(ResultHelper::SUCCESS_CODE, "发货成功");
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ResultHelper::json(ResultHelper::ERROR_CODE, $e->getMessage());
        }
    }

    /**
     * 退货
     */
    public function actionReturnGoods()
    {
//        RedisHelper::verify($this->memberId, $this->action->id);
//        $id = Yii::$app->request->post('id');
//        $return_goods_time = Yii::$app->debris->backendConfig('return_goods_time');
//        $order = Orders::find()
//            ->where(['id' => $id, 'status' => 0])
//            ->andWhere(['<', 'created_at', time() - $return_goods_time * 86400])
//            ->one();
//        if (empty($order)) {
//            return ResultHelper::json(ResultHelper::ERROR_CODE, '该订单尚未到达退货时间');
//        }
//        $order->status = 2;
//        $order->save();
//        // 退货增加买家余额
//        Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
//            'member' => Member::findOne($this->memberId),
//            'pay_type' => CreditsLog::BUY_SHORT_PLAYS_TYPE,
//            'num' => $order->money,
//            'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
//            'remark' => "【短剧】买家退货",
//        ]));
//        // 卖家扣除信用分
//        $seller_member = Member::findOne($order->seller_id);
//        $seller_member->credit_score = $seller_member->credit_score - 1;
//        $seller_member->save();
//
//
//        // 预售退款税
//        $pre_sale_refund_tax = Yii::$app->debris->backendConfig('pre_sale_refund_tax');
//        if ($pre_sale_refund_tax > 0) {
//            $money = BcHelper::mul(BcHelper::div($pre_sale_refund_tax, 100, 4), $order->money);
//        } else {
//            $money = $order->money;
//        }
//
//        // 返回卖家预售金额
//        Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
//            'member' => Member::findOne($order->seller_id),
//            'pay_type' => CreditsLog::SEND_SHORT_PLAYS_TYPE,
//            'num' => $money,
//            'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
//            'remark' => "【预售】上架预售返还余额",
//        ]));
//
//
//        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "退货成功");
    }


    /**
     * 短剧加密内容
     * @return string
     */
    public function actionDetail()
    {
        $host = Yii::$app->debris->backendConfig('short_plays_video_url');
        $url = Yii::$app->request->get('video_url');
        $start = strpos($url, '/auto');
        $end = strpos($url, '.m3u8');
        $str = substr($url, $start, $end - 6);
        $key = "abcd123456";
        $time = CommonPluginHelper::msectime() + (60 * 1000 * 60);
        $counts = 3;
        $str2 = $str . "&counts=$counts&timestamp=" . $time . $key;
        $sign = md5($str2);
//        return htmlentities($host . $url . "?counts=$counts&timestamp=" . $time . "&key=" . $sign);
        return $host . $url . "?counts=$counts&timestamp=" . $time . "&key=" . $sign;
    }

    /**
     * 权限验证
     * @param string $action
     * @param null $model
     * @param array $params
     * @throws \yii\web\BadRequestHttpException
     * @author 原创脉冲
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        // 方法名称
        if (in_array($action, ['view', 'update', 'delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}