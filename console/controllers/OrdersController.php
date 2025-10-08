<?php


namespace console\controllers;


use common\enums\StatusEnum;
use common\helpers\BcHelper;
use common\models\common\Statistics;
use common\models\dj\Orders;
use common\models\dj\PromotionDetail;
use common\models\dj\PromotionOrder;
use common\models\dj\SellerAvailableList;
use common\models\dj\SellerAvailableOrder;
use common\models\dj\ShortPlaysDetail;
use common\models\dj\ShortPlaysList;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use yii\console\Controller;
use Yii;
use yii\db\Expression;

class OrdersController extends Controller
{
    /**
     * 增加收益
     */
    public function actionIndex()
    {
        $orders = Orders::find()
            ->where(['income_status' => 0, 'status' => 1])
            ->andWhere(['<', 'over_time', time()])
            ->all();
        foreach ($orders as $order) {
            $order->income_status = 1;
            $order->over_time = time();
            $order->save();
            $member = Member::find()->where(['id' => $order->seller_id])->with(['account', 'sellerLevel'])->one();

            // 卖家获得本金和利润
            Yii::$app->services->memberCreditsLog->incrCanWithdrawMoney(new CreditsLogForm([
                'member' => $member,
                'pay_type' => CreditsLog::FB_TYPE,
                'num' => $order->dx_money,
                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                'remark' => "【收益】退还代销费",
            ]));
            if ($order->income > 0) {
                Yii::$app->services->memberCreditsLog->incrCanWithdrawMoney(new CreditsLogForm([
                    'member' => $member,
                    'pay_type' => CreditsLog::INCOME_TYPE,
                    'num' => $order->income,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => "【收益】销售短剧获取收益",
                ]));
                // 如果是推流订单，则扣除推流费用
                if ($order->push_flow_switch == 1) {
                    $push_flow = $member->sellerLevel->push_flow;
                    $push_flow_money = BcHelper::mul($order->income, BcHelper::div($push_flow, 100, 4));
                    if ($push_flow_money > 0) {
                        Yii::$app->services->memberCreditsLog->decrCanWithdrawMoney(new CreditsLogForm([
                            'member' => $member,
                            'pay_type' => CreditsLog::PROMOTION_TYPE,
                            'num' => $push_flow_money,
                            'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                            'remark' => "【推流】扣除手续费",
                        ]));
                    }


                    $promotion = PromotionOrder::find()->where(['member_id' => $member->id, 'status' => 1])->one();
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
                        // 收益增减
                        $promotion_detail->doing_money = BcHelper::sub($promotion_detail->doing_money, $order->income);
                        $promotion_detail->over_money = BcHelper::add($promotion_detail->over_money, $order->income);
                        $promotion_detail->save(false);
                    }
                }

                // 增加总收益
                $member->account->investment_income = BcHelper::add($member->account->investment_income, $order->income);
                $member->account->save(false);

//                // 查看买家上级是否返点
//                if ($member['id'] == 1) {
//                    $buyer = Member::find()->where(['id' => $order->member_id])->select(['pid'])->asArray()->one();
//                    if (!empty($buyer['pid']) && $buyer['pid'] != 1) {
//                        // 买家上级利润返点
//                        $profit_rebate = Yii::$app->debris->backendConfig('profit_rebate');
//                        $profit_rebate_money = BcHelper::mul(BcHelper::div($profit_rebate, 100, 4), $order->income);
//                        if ($profit_rebate_money > 0) {
//                            $seller = Member::find()->where(['id' => $buyer['pid']])->with(['account'])->one();
//                            Yii::$app->services->memberCreditsLog->incrCanWithdrawMoney(new CreditsLogForm([
//                                'member' => $seller,
//                                'pay_type' => CreditsLog::INCOME_TYPE,
//                                'num' => $profit_rebate_money,
//                                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
//                                'remark' => "【收益】销售短剧获取收益返点",
//                            ]));
//                            $seller->account->contract_profit = BcHelper::add($seller->account->contract_profit, $profit_rebate_money);
//                            $non_contractual_profit = BcHelper::sub($order->income, $profit_rebate_money);
//                            $seller->account->non_contractual_profit = BcHelper::add($seller->account->non_contractual_profit, $non_contractual_profit);
//                            $seller->save(false);
//                        }
//                    }
//                }
                // 合约利润和非合约利润
                $seller_id = $order->seller_id;
                $seller_order = Orders::find()->where(['id' => $order->id])
                    ->with([
                        'shortPlaysList' => function ($query) use ($seller_id) {
                            $query->with([
                                'sellerAvailableList' => function ($query) use ($seller_id) {
                                    $query->where(['member_id' => $seller_id]);
                                }
                            ]);
                        }
                    ])
                    ->asArray()
                    ->one();

                // 若是已上架
                if (!empty($seller_order['shortPlaysList']['sellerAvailableList'])) {
                    $member->account->contract_profit = $order->income;
                } else {
                    $member->account->non_contractual_profit = $order->income;
                }
                $member->account->save(false);


                // 加入统计表 获取最上级用户ID 并且不是系统卖家
                if ($member['type'] == 1 && $member['id'] != 1) {
                    $first_member = Member::getParentsFirst($member);
                    $b_id = $first_member['b_id'] ?? 0;
                    Statistics::updateIncomeMoney(date("Y-m-d"), $order->income, $b_id);
                    // 发放佣金
                    if (!empty($member['pid'])) {
                        $commission_one = BcHelper::mul($order->income, BcHelper::div(Yii::$app->debris->backendConfig('commission_one'), 100, 4));
                        $member_one = Member::findOne($member['pid']);
                        if ($commission_one > 0) {
                            Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                                'member' => $member_one,
                                'pay_type' => CreditsLog::COMMISSION_TYPE,
                                'num' => $commission_one,
                                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                                'remark' => "【返佣】获得一级返佣",
                            ]));
                            Statistics::updateCommissionMoney(date("Y-m-d"), $commission_one, $b_id);
                            if (!empty($member_one['pid'])) {
                                $commission_two = BcHelper::mul($order->income, BcHelper::div(Yii::$app->debris->backendConfig('commission_two'), 100, 4));
                                $member_two = Member::findOne($member_one['pid']);
                                if ($commission_two > 0) {
                                    Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                                        'member' => $member_two,
                                        'pay_type' => CreditsLog::COMMISSION_TYPE,
                                        'num' => $commission_two,
                                        'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                                        'remark' => "【返佣】获得二级返佣",
                                    ]));
                                    Statistics::updateCommissionMoney(date("Y-m-d"), $commission_two, $b_id);
                                    if (!empty($member_two['pid'])) {
                                        $commission_three = BcHelper::mul($order->income, BcHelper::div(Yii::$app->debris->backendConfig('commission_three'), 100, 4));
                                        $member_three = Member::findOne($member_two['pid']);
                                        if ($member_three > 0) {
                                            Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                                                'member' => $member_three,
                                                'pay_type' => CreditsLog::COMMISSION_TYPE,
                                                'num' => $commission_three,
                                                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                                                'remark' => "【返佣】获得三级返佣",
                                            ]));
                                            Statistics::updateCommissionMoney(date("Y-m-d"), $commission_three, $b_id);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $this->stdout(date('Y-m-d H:i:s') . " ------ 完成" . PHP_EOL);
    }

    /**
     * 自动增加购买量和播放量
     */
    public function actionAdd()
    {
        $models = ShortPlaysList::find()->where(['status' => StatusEnum::ENABLED])->all();
        foreach ($models as $model) {
            $add_number_interval = Yii::$app->debris->backendConfig('add_number_interval');
            $add_number_interval = explode("-", $add_number_interval);
            $model->number += rand($add_number_interval[0], $add_number_interval[1]);

            $add_buy_number_interval = Yii::$app->debris->backendConfig('add_buy_number_interval');
            $add_buy_number_interval = explode("-", $add_buy_number_interval);
            $model->buy_number += rand($add_buy_number_interval[0], $add_buy_number_interval[1]);
            $model->save(false);
        }
        $this->stdout(date('Y-m-d H:i:s') . " ------ 完成" . PHP_EOL);
    }

    /**
     * 自动增点赞量和收藏量
     */
    public function actionAddDetail()
    {
        $models = ShortPlaysDetail::find()->where(['status' => StatusEnum::ENABLED])->all();
        foreach ($models as $model) {
            $add_like_number_interval = Yii::$app->debris->backendConfig('add_like_number_interval');
            $add_like_number_interval = explode("-", $add_like_number_interval);
            $model->like_number += rand($add_like_number_interval[0], $add_like_number_interval[1]);

            $add_collect_number__interval = Yii::$app->debris->backendConfig('add_collect_number__interval');
            $add_collect_number__interval = explode("-", $add_collect_number__interval);
            $model->collect_number += rand($add_collect_number__interval[0], $add_collect_number__interval[1]);
            $model->save(false);
        }
        $this->stdout(date('Y-m-d H:i:s') . " ------ 完成" . PHP_EOL);
    }

    /**
     * 更新短剧是否最新
     */
    public function actionIsNew()
    {
        ShortPlaysList::updateAll(['is_new' => 0], ['and', ['<', 'created_at', time() - 7 * 86400], ['is_new' => 1]]);
        $this->stdout(date('Y-m-d H:i:s') . " ------ 完成" . PHP_EOL);
    }

    /**
     * 自动退货
     */
    public function actionReturnGoods()
    {
//        $return_goods_time = Yii::$app->debris->backendConfig('return_goods_time');
//        $orders = Orders::find()
//            ->where(['status' => 0])
//            ->andWhere(['<', 'created_at', time() - $return_goods_time * 86400])
//            ->all();
//        foreach ($orders as $order) {
//            $order->status = 2;
//            $order->save();
//
//            $member = Member::findOne($order->member_id);
//
//            // 真实用户退还买短剧的费用
//            if ($member->is_virtual == 0) {
//                // 退货增加买家余额
//                Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
//                    'member' => $member,
//                    'pay_type' => CreditsLog::BUY_SHORT_PLAYS_TYPE,
//                    'num' => $order->money,
//                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
//                    'remark' => "【短剧】买家退货",
//                ]));
//            }
//
//            // 卖家扣除信用分
//            $seller_member = Member::findOne($order->seller_id);
//            $seller_member->credit_score = $seller_member->credit_score - 1;
//            $seller_member->save();
//
//
//            // 预售退款税
//            $pre_sale_refund_tax = Yii::$app->debris->backendConfig('pre_sale_refund_tax');
//            if ($pre_sale_refund_tax > 0) {
//                $money = BcHelper::mul(BcHelper::div($pre_sale_refund_tax, 100, 4), $order->money);
//            } else {
//                $money = $order->money;
//            }
//
//            // 返回卖家预售金额
//            Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
//                'member' => Member::findOne($order->seller_id),
//                'pay_type' => CreditsLog::SEND_SHORT_PLAYS_TYPE,
//                'num' => $money,
//                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
//                'remark' => "【预售】上架预售返还余额",
//            ]));
//
//        }
//        $this->stdout(date('Y-m-d H:i:s') . " ------ 完成" . PHP_EOL);
    }

    /**
     * 自动发货
     */
    public function actionAutomaticDelivery()
    {
//        $automatic_delivery_time = Yii::$app->debris->backendConfig('automatic_delivery_time');
//        $orders = Orders::find()
//            ->where(['status' => 0])
//            ->andWhere(['<', 'created_at', time() - ($automatic_delivery_time * 3600)])
//            ->with([
//                'seller' => function ($query) {
//                    $query->with(['account', 'sellerLevel']);
//                }
//            ])
//            ->all();
//        foreach ($orders as $order) {
//            if ($order->seller->automatic_delivery_switch == 1 && $order->seller->account->user_money >= $order->dx_money) {
//
//                // 扣除卖家余额
////                Yii::$app->services->memberCreditsLog->decrMoney(new CreditsLogForm([
////                    'member' => $order->seller,
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
//                $order->over_time = BcHelper::add(BcHelper::mul(86400, $order->seller->sellerLevel->return_income_time, 0), $time, 0);
//
//
//                // 发货成功，发送站内信
//                Yii::$app->services->memberNotify->createMessage("发货通知", "您购买的短剧订单已发货，短剧密钥为：" . $order->private_key, 1, [$order->member_id], time());
//
//                // 统计订单总数和销售额
//                $order->seller->account->investment_all_money = BcHelper::add($order->seller->account->investment_all_money, $order->money);
//                $order->seller->account->investment_number = $order->seller->account->investment_number + 1;
//                $order->seller->account->save(false);
//
//                // 用户升级 更新等级
//                Yii::$app->services->memberLevel->updateSellerLevel($order->seller);
//
//                // 统计销售额 加入统计表 获取最上级用户ID
//                if ($order->seller['type'] == 1) {
//                    $first_member = Member::getParentsFirst($order->seller);
//                    $b_id = $first_member['b_id'] ?? 0;
//                    Statistics::updateBuyMoney(date("Y-m-d"), $order->money, $b_id);
//                }
//
//                // 判断卖家是否开启推流
//                if ($order->seller->push_flow_switch == 1) {
//                    $promotion = PromotionOrder::find()->where(['member_id' => $order->seller->id, 'status' => 1])->one();
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
//
//            }
//        }
//        $this->stdout(date('Y-m-d H:i:s') . " ------ 完成" . PHP_EOL);
    }

    /**
     * 推流下单
     */
//    public function actionPromotion()
//    {
//        $promotions = PromotionOrder::find()->where(['status' => 1])->all();
//        foreach ($promotions as $promotion) {
//            // 首先扣除订单量
//            $number = rand(1, 5);
//            $old_number = $promotion->number;
//            $promotion->number = $promotion->number - $number;
//            if ($promotion->number < 0) {
//                $promotion->number = 0;
//                $number = $old_number;
//                $promotion->status = 0;
//            }
//            $promotion->save(false);
//            $date = date("Y-m-d");
//            // 添加详情
//            $promotion_detail = PromotionDetail::find()->where(['pid' => $promotion->id, 'created_at' => $date])->one();
//            if (empty($promotion_detail)) {
//                $promotion_detail = new PromotionDetail();
//                $promotion_detail->member_id = $promotion->member_id;
//                $promotion_detail->pid = $promotion->id;
//                $promotion_detail->created_at = $date;
//            }
//            $promotion_detail->clicks += rand(5, 10);
//            $promotion_detail->quantity += $number;
//            $promotion_detail->save(false);
//            // 开始下单
//            for ($i = 0; $i < $number; $i++) {
//                // 开启事务
//                $transaction = Yii::$app->db->beginTransaction();
//                try {
//                    // 首先选择用户上架了的短剧
//                    $sellerAvailableList = SellerAvailableList::find()
//                        ->select(['pid'])
//                        ->where(['member_id' => $promotion->member_id])
//                        ->orderBy(new Expression('rand()'))
//                        ->one();
//                    $seller_id = $promotion->member_id;
//                    // 如果没有上架过短剧，则随便找个短剧
//                    if (empty($sellerAvailableList)) {
//                        $shortPlaysList = ShortPlaysList::find()
//                            ->orderBy(new Expression('rand()'))
//                            ->one();
//                    } else {
//                        $pid = $sellerAvailableList['pid'];
//
//                        $shortPlaysList = ShortPlaysList::find()
//                            ->where(['id' => $pid])
//                            ->with([
//                                'sellerAvailableList' => function ($query) use ($seller_id) {
//                                    $query->where(['member_id' => $seller_id]);
//                                }
//                            ])
//                            ->one();
//                    }
//                    $sellerMember = Member::find()->where(['id' => $seller_id])->with(['sellerLevel'])->one();
//                    // 如果卖家未上架短剧
//                    if (empty($shortPlaysList['sellerAvailableList'])) {
//                        $income = BcHelper::mul(BcHelper::div($sellerMember->sellerLevel->profit_rebate, 100, 4), $shortPlaysList->amount);
//                        $dx_money = BcHelper::sub($shortPlaysList->amount, $income);
//                    } else {
//                        $income = BcHelper::mul(BcHelper::div($sellerMember->sellerLevel->profit, 100, 4), $shortPlaysList->amount);
//                        $dx_money = BcHelper::sub($shortPlaysList->amount, $income);
//                    }
//                    // 寻找买家
//                    $member = Member::find()->where(['pid' => $seller_id, 'is_virtual' => 1])->orderBy(new Expression('rand()'))->one();
//                    if (empty($member)) {
//                        // 如没有虚拟买家，则随便找个
//                        $member = Member::find()->where(['is_virtual' => 1])->orderBy(new Expression('rand()'))->one();
//                    }
//
//
//                    $order_model = new Orders();
//                    $order_model->seller_id = $seller_id;
//                    $order_model->pid = $shortPlaysList->id;
//                    $order_model->money = $shortPlaysList->amount;
//                    $order_model->dx_money = $dx_money;
//                    $order_model->income = $income;
//                    $order_model->private_key = md5($pid . time());
//                    // 处理买家
//                    $order_model->member_id = $member->id;
//                    $time = time();
//                    $order_model->created_at = $time;
//                    $order_model->save();
//                    $shortPlaysList->number += $promotion_detail->clicks;
//                    $shortPlaysList->buy_number += 1;
//                    $shortPlaysList->save();
//                    // 新订单站内信
//                    Yii::$app->services->memberNotify->createMessage("新的订单", "您有新的订单，请前往订单列表查看", 1, [$order_model->seller_id], $time);
//                    $transaction->commit();
//                } catch (\Exception $e) {
//                    $transaction->rollBack();
//                    continue;
//                }
//            }
//        }
//        $this->stdout(date('Y-m-d H:i:s') . " ------ 完成" . PHP_EOL);
//    }


    /**
     * 推流下单
     */
    public function actionPromotion()
    {
        $promotions = PromotionOrder::find()->where(['status' => 1])->all();
        foreach ($promotions as $promotion) {
            $date = date("Y-m-d");
            // 添加详情
            $promotion_detail = PromotionDetail::find()->where(['pid' => $promotion->id, 'created_at' => $date])->one();
            if (empty($promotion_detail)) {
                $promotion_detail = new PromotionDetail();
                $promotion_detail->member_id = $promotion->member_id;
                $promotion_detail->pid = $promotion->id;
                $promotion_detail->created_at = $date;
            }
            $promotion_detail->clicks += rand(5, 10);
            $promotion_detail->save(false);
        }
        $this->stdout(date('Y-m-d H:i:s') . " ------ 完成" . PHP_EOL);
    }

    /**
     * 退回预售金额
     */
    public function actionPreSaleReturn()
    {
        $return_pre_sale_time = Yii::$app->debris->backendConfig('return_pre_sale_time');
        if ($return_pre_sale_time > 0) {
            $orders = SellerAvailableOrder::find()
                ->where([">", 'buy_number', 0])
                ->andWhere(['<', 'created_at', time() - ($return_pre_sale_time * 86400)])
                ->all();
            foreach ($orders as $order) {
                $order->buy_number = 0;
                $order->save(false);
                // 判断短剧是否该下架
                if (SellerAvailableOrder::find()->where(['member_id' => $order['member_id'], 'pid' => $order['pid']])->sum('buy_number') == 0) {
                    $sellerAvailableList = SellerAvailableList::find()->where(['member_id' => $order['member_id'], 'pid' => $order['pid']])->one();
                    if (!empty($sellerAvailableList) && $order['member_id'] != 1) {
                        $sellerAvailableList->status = 0;
                        $sellerAvailableList->save(false);
                    }

                }
                // 返回卖家预售金额
                $pre_sale_refund_tax = Yii::$app->debris->backendConfig('pre_sale_refund_tax');
                if ($pre_sale_refund_tax > 0) {
                    $money = BcHelper::sub($order->buy_money, BcHelper::mul(BcHelper::div($pre_sale_refund_tax, 100, 4), $order->buy_money));
                } else {
                    $money = $order->buy_money;
                }
                Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                    'member' => Member::findOne($order->member_id),
                    'pay_type' => CreditsLog::SEND_SHORT_PLAYS_TYPE,
                    'num' => $money,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => "【预售】上架预售返还余额",
                ]));
            }
        }
        $this->stdout(date('Y-m-d H:i:s') . " ------ 完成" . PHP_EOL);
    }
}
