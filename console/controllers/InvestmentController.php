<?php


namespace console\controllers;


use common\enums\StatusEnum;
use common\helpers\BcHelper;
use common\models\common\Statistics;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use common\models\tea\InvestmentBill;
use common\models\tea\InvestmentProject;
use yii\console\Controller;
use Yii;

class InvestmentController extends Controller
{
    /**
     * 自动增长项目进度
     */
    public function actionIncrease()
    {
        // 解除内存限制
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $models = InvestmentProject::find()
            ->where(['increase_status' => StatusEnum::ENABLED, 'status' => 1])
            ->all();
        foreach ($models as $model) {
            $investment_amount = BcHelper::mul($model->increase_times, $model->least_amount);
            // 更新项目内容 能投资的金额
            $model->can_investment_amount = BcHelper::sub($model->can_investment_amount, $investment_amount);
            if ($model->can_investment_amount < 0) {
                $model->can_investment_amount = 0;
            }
            // 项目进度
            $model->schedule = BcHelper::mul(BcHelper::div(($model->all_investment_amount - $model->can_investment_amount), $model->all_investment_amount, 4));
            if ($model->schedule == 100) {
                $model->status = 0;
            }
            // 投资人数
            $model->investment_number += 1;
            $model->save(false);
        }
        $this->stdout(date('Y-m-d H:i:s') . " ------ InvestmentProject increase is ok!" . PHP_EOL);
    }


    public function actionIndex()
    {
        // 解除内存限制
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        // 拿出符合条件结算的订单
        $lists = InvestmentBill::find()
            ->where(['<=', 'next_time', time()])
            ->andWhere(['<', 'status', 3])
            ->andWhere(['<>', 'settlement_times', 0])
            ->with([
                'investmentProject',
                'member' => function ($query) {
                    $query->with(['memberLevel']);
                },
                'account',
            ])
            ->all();
        // 计算收益
        foreach ($lists as $v1) {
            if ($v1->category == 1) {
                $this->tianJiesuan($v1);
            } elseif ($v1->category == 2) {
                $this->yueJiesuan($v1);
            } elseif ($v1->category == 3) {
                $this->zhouqiJiesuan($v1);
            } elseif ($v1->category == 4) {
                $this->rifuliJiesuan($v1);
            }
        }

        // 结算收益方式
        $settlement_income_method = Yii::$app->debris->config('settlement_income_method');
        // 自动结算
        if ($settlement_income_method == 1) {
            // 拿出符合条件结算的订单
            $lists = InvestmentBill::find()
                ->where(['>', 'income_amount', 0])
                ->andWhere(['<>', 'status', 4])
                ->with(['investmentProject', 'member', 'account',])
                ->all();
            foreach ($lists as $v1) {
                $this->jiesuan($v1);
            }
            $this->stdout(date('Y-m-d H:i:s') . " ------ Automatic Settlement Income" . PHP_EOL);
        } else {
            $this->stdout(date('Y-m-d H:i:s') . " ------ Manual Settlement Income" . PHP_EOL);
        }
    }

    /**
     * 按天结算
     */
    public function tianJiesuan($v1)
    {
        //开启事务
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 操作订单
            $v1->settlement_times = $v1->settlement_times - 1;
            if ($v1->settlement_times == 0) {
                $v1->status = 3;
                // 应退还本金
                $v1->income_amount = BcHelper::add($v1->income_amount, $v1->investment_amount, 2);
                // 退还本金后,减掉用户再投金额
                $v1->account->investment_doing_money = BcHelper::sub($v1->account->investment_doing_money, $v1->investment_amount, 2);
                $v1->account->save(false);
                // 判断在投金额,如果等于0,说明用户未投了,用户投资状态变成2
                if ($v1->account->investment_doing_money == 0) {
                    $v1->member->investment_status = 2;
                    $v1->member->save(false);
                }
            } else {
                $v1->status = 2;
                // 下次结算时间
                $v1->next_time = $v1->next_time + (24 * 60 * 60);
            }

            // 计算收益 额外收益率
            $vip_income = $v1->add_income;
            $income = BcHelper::mul($v1->investment_amount, BcHelper::add(BcHelper::div($v1->investmentProject->income, 100, 4), BcHelper::div($v1->add_income, 100, 4), 4));
            $additional_income = BcHelper::mul($v1->investment_amount, BcHelper::div($vip_income, 100, 4));
            $v1->income_amount_all = BcHelper::add($v1->income_amount_all, $income);
            $v1->income_amount = BcHelper::add($v1->income_amount, $income);
            $v1->additional_income = BcHelper::add($v1->additional_income, $additional_income);
            $v1->save(false);
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollback();
            return false;
        }
    }

    /**
     * 按月结算
     */
    public function yueJiesuan($v1)
    {
        //开启事务
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 操作订单
            $v1->settlement_times = $v1->settlement_times - 1;
            if ($v1->settlement_times == 0) {
                $v1->status = 3;
                // 应退还本金
                $v1->income_amount = BcHelper::add($v1->income_amount, $v1->investment_amount, 2);
                // 退还本金后,减掉用户再投金额
                $v1->account->investment_doing_money = BcHelper::sub($v1->account->investment_doing_money, $v1->investment_amount, 2);
                $v1->account->save(false);
                // 判断在投金额,如果等于0,说明用户未投了,用户投资状态变成2
                if ($v1->account->investment_doing_money == 0) {
                    $v1->member->investment_status = 2;
                    $v1->member->save(false);
                }
            } else {
                $v1->status = 2;
                // 更新下一次结算时间
                $v1->next_time = $v1->next_time + (30 * 24 * 60 * 60);
            }

            // 计算收益 额外收益率
            $vip_income = $v1->add_income;
            $income = BcHelper::mul(BcHelper::mul($v1->investment_amount, BcHelper::add(BcHelper::div($v1->investmentProject->income, 100, 4), BcHelper::div($v1->add_income, 100, 4), 4), 4), 30);
            $additional_income = BcHelper::mul(BcHelper::mul($v1->investment_amount, BcHelper::div($vip_income, 100, 4), 4), 30);
            $v1->income_amount_all = BcHelper::add($v1->income_amount_all, $income);
            $v1->income_amount = BcHelper::add($v1->income_amount, $income);
            $v1->additional_income = BcHelper::add($v1->additional_income, $additional_income);
            $v1->save(false);
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollback();
            return false;
        }
    }

    /**
     * 按周期结算
     */
    public function zhouqiJiesuan($v1)
    {
        //开启事务
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 操作订单
            $v1->settlement_times = 0;
            $v1->status = 3;
            // 应退还本金
            $v1->income_amount = BcHelper::add($v1->income_amount, $v1->investment_amount, 2);
            // 退还本金后,减掉用户再投金额
            $v1->account->investment_doing_money = BcHelper::sub($v1->account->investment_doing_money, $v1->investment_amount, 2);
            $v1->account->save(false);
            // 判断在投金额,如果等于0,说明用户未投了,用户投资状态变成2
            if ($v1->account->investment_doing_money == 0) {
                $v1->member->investment_status = 2;
                $v1->member->save(false);
            }
            // 计算收益 额外收益率
            $vip_income = $v1->add_income;
            $income = BcHelper::mul(BcHelper::mul($v1->investment_amount, BcHelper::add(BcHelper::div($v1->investmentProject->income, 100, 4), BcHelper::div($v1->add_income, 100, 4), 4), 4), $v1->investmentProject->deadline);
            $additional_income = BcHelper::mul(BcHelper::mul($v1->investment_amount, BcHelper::div($vip_income, 100, 4), 4), $v1->investmentProject->deadline);
            $v1->income_amount_all = BcHelper::add($v1->income_amount_all, $income);
            $v1->income_amount = BcHelper::add($v1->income_amount, $income);
            $v1->additional_income = BcHelper::add($v1->additional_income, $additional_income);
            $v1->save(false);
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollback();
            return false;
        }
    }


    /**
     * 按每日复利结算
     */
    public function rifuliJiesuan($v1)
    {

        //开启事务
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 操作订单
            $v1->settlement_times = 0;
            $v1->status = 3;
            // 应退还本金
            $v1->income_amount = BcHelper::add($v1->income_amount, $v1->investment_amount, 2);
            // 退还本金后,减掉用户再投金额
            $v1->account->investment_doing_money = BcHelper::sub($v1->account->investment_doing_money, $v1->investment_amount, 2);
            $v1->account->save(false);
            // 判断在投金额,如果等于0,说明用户未投了,用户投资状态变成2
            if ($v1->account->investment_doing_money == 0) {
                $v1->member->investment_status = 2;
                $v1->member->save(false);
            }
            // 计算收益 额外收益率
            $vip_income = $v1->add_income;
            $lixi = pow(BcHelper::add(1, BcHelper::add(BcHelper::div($v1->investmentProject->income, 100, 4), BcHelper::div($v1->add_income, 100, 4), 4), 4), $v1->investmentProject->deadline);
            $income = BcHelper::sub(BcHelper::mul($v1->investment_amount, $lixi, 4), $v1->investment_amount);
            $additional_lixi = pow(BcHelper::add(1, BcHelper::div($vip_income, 100, 4), 4), $v1->investmentProject->deadline);
            $additional_income = BcHelper::sub(BcHelper::mul($v1->investment_amount, $additional_lixi, 4), $v1->investment_amount);
            $v1->income_amount_all = BcHelper::add($v1->income_amount_all, $income);
            $v1->income_amount = BcHelper::add($v1->income_amount, $income);
            $v1->additional_income = BcHelper::add($v1->additional_income, $additional_income);
            $v1->save(false);
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollback();
            return false;
        }
    }

    /**
     * 结算收益
     * @param $v1
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function jiesuan($v1)
    {
        if ($v1->income_amount > 0) {
            $income_amount = $v1->income_amount;
            if ($v1->settlement_times == 0) {
                //首先退还本金
                Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                    'member' => Member::findOne($v1->member->id),
                    'pay_type' => CreditsLog::FB_TYPE,
                    'num' => $v1->investment_amount,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => "【退本】购买产品：" . $v1->investmentProject->title . "，周期结束退还本金" . $v1->investment_amount . "元",
                    'map_id' => $v1->id,
                ]));
                //那么本金也退还了 实际收益就是
                $income_amount = BcHelper::sub($income_amount, $v1->investment_amount);
                // 判断项目类型 赠送红包
                if (
                    (
                        empty($v1->investmentProject->gift_amount_time) &&
                        $v1->investmentProject->gift_method == 2 &&
                        $v1->investmentProject->gift_amount > 0
                    )
                    ||
                    (
                        !empty($v1->member->pid) &&
                        $v1->investmentProject->gift_method == 2 &&
                        $v1->investmentProject->gift_amount > 0 &&
                        $v1->member->created_at > $v1->investmentProject->gift_amount_time
                    )
                ) {
                    // 红包金额
                    $gift_amount = BcHelper::mul(BcHelper::div($v1->investment_amount, $v1->investmentProject->least_amount, 0), $v1->investmentProject->gift_amount);
                    // 添加日志更新用户余额
                    Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                        'member' => Member::findOne($v1->member->id),
                        'num' => $gift_amount,
                        'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                        'remark' => '【红包】购买活动产品周期完成，获得奖金',
                        'pay_type' => CreditsLog::GIFT_TYPE,
                    ]));
                }
                // 判断返现类型
                if ($v1->investmentProject->return_method == 2 && $v1->investmentProject->return_percentage > 0) {
                    // 添加日志更新用户余额
                    $return_amount = BcHelper::mul($v1->investment_amount, BcHelper::div($v1->investmentProject->return_percentage, 100, 4));
                    Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                        'member' => Member::findOne($v1->member->id),
                        'num' => $return_amount,
                        'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                        'remark' => '【红包】购买活动产品周期完成，获得返现',
                        'pay_type' => CreditsLog::GIFT_TYPE,
                    ]));
                }
            }
            // 添加收益
            Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                'member' => Member::findOne($v1->member->id),
                'pay_type' => CreditsLog::INCOME_TYPE,
                'num' => $income_amount,
                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                'remark' => "【产品收益】购买产品：" . $v1->investmentProject->title . "，获得收益" . $income_amount . "元",
                'map_id' => $v1->id,
            ]));
            $v1->income_amount = 0;
            $v1->save(false);

            // 添加个人统计
            $v1->account->investment_income = BcHelper::add($v1->account->investment_income, $income_amount);
            $v1->account->save(false);
            // 加入收益统计
            Statistics::updateIncomeMoney(date('Y-m-d'), $income_amount);
        }
    }
}
