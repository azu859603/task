<?php

namespace api\modules\v1\controllers\tea;

use api\controllers\OnAuthController;
use common\enums\StatusEnum;
use common\helpers\BcHelper;
use common\helpers\DateHelper;
use common\helpers\FileHelper;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\common\Statistics;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use common\models\tea\ActivityCardList;
use common\models\tea\ActivityCardSetting;
use common\models\tea\CouponMember;
use common\models\tea\InvestmentBill;
use common\models\tea\InvestmentProject;
use Yii;
use yii\data\ActiveDataProvider;

class InvestmentBillController extends OnAuthController
{
    public $modelClass = InvestmentBill::class;


    /**
     * 投资列表
     * @return ActiveDataProvider
     */
    public function actionIndex()
    {
        $status = Yii::$app->request->get('status', 1);
        return new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->where(['member_id' => $this->memberId, 'status' => $status])
                ->select([
                    'id',
                    'category',
                    'income_amount_all',
                    'investment_amount',
                    'project_id',
                    'status',
                    'send_remark',
                    'send_status',
                ])
                ->orderBy([
                    'created_at' => SORT_DESC,
                ])
                ->with(['investmentProject' => function ($query) {
                    $query->select(['id', 'title', 'project_img', 'category']);
                }])
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
    }


    /**
     * 添加购买订单
     * @return array|mixed|\yii\db\ActiveRecord
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionCreate()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        // 验证ID
        $id = Yii::$app->request->post('id');
        if (empty($id)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '产品信息错误！');
        }
        // 判断项目状态
        if (empty($investmentProject = InvestmentProject::find()->where(['id' => $id, 'status' => StatusEnum::ENABLED, 'project_status' => StatusEnum::ENABLED])->with(['ch', 'cj'])->one())) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '该产品已暂停参与购买，请购买其他产品！');
        }
        // 判断秒杀时间
        if ($investmentProject->spike_type == 1) { // 若开启秒杀
            if (time() < $investmentProject->spike_start_time) {
                return ResultHelper::json(ResultHelper::ERROR_CODE, '该产品秒杀活动暂未开始，活动时间为' . date("Y-m-d H:i:s", $investmentProject->spike_start_time) . "至" . date("Y-m-d H:i:s", $investmentProject->spike_stop_time) . '！');
            }
            if (time() > $investmentProject->spike_stop_time) {
                return ResultHelper::json(ResultHelper::ERROR_CODE, '该产品秒杀活动已结束，活动时间为' . date("Y-m-d H:i:s", $investmentProject->spike_start_time) . "至" . date("Y-m-d H:i:s", $investmentProject->spike_stop_time) . '！');
            }
        }
        // 判断投资金额
        $investment_amount = Yii::$app->request->post('investment_amount');
        if (empty($investment_amount)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '购买金额不能为空！');
        }
        // 判断是否实名制
        $memberInfo = Member::find()
            ->where(['id' => $this->memberId])
            ->with(['account', 'memberLevel'])
            ->one();
        if (empty($memberInfo->realname)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '请您先实名认证后再继续操作');
        }

        // 判断项目会员等级
        if ($investmentProject->vip_level > $memberInfo->current_level) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '当前会员等级不能进行购买此产品！');
        }

        // 验证安全密码
        $safety_password = Yii::$app->request->post('safety_password');
        if (empty($safety_password)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "安全密码不能为空！");
        }
        $reslut = $memberInfo->validateSafetyPassword($safety_password);
        if (!$reslut) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "安全密码错误！");
        }
        if ($investment_amount > $memberInfo->account->user_money) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '您的余额不足，请前往充值！');
        }
        if ($investment_amount < $investmentProject->least_amount) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '起始购买金额为' . $investmentProject->least_amount . '元！');
        }
        if ($investmentProject->most_amount > 0 && $investment_amount > $investmentProject->most_amount) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '该产品封顶购买金额为' . $investmentProject->most_amount . '元！');
        }
        if ($investment_amount > $investmentProject->can_investment_amount) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '已超过该产品可购买额！');
        }
        // 判断是否限制次数
        if ($investmentProject->limit_times > 0) {
            // 如果限制了次数,先取出用户投资此项目的次数
            $investmentBillCount = InvestmentBill::getInvestmentBillCount($id, $this->memberId);
            if ($investmentBillCount >= $investmentProject->limit_times) {
                return ResultHelper::json(ResultHelper::ERROR_CODE, '该产品每人只能购买' . $investmentProject->limit_times . '次！');
            }
        }
        // 24小时后结算
        $settlementTime = time();
        // 开启事务同步更新
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $model = new InvestmentBill();
            $name = Yii::$app->request->post('name');
            $mobile = Yii::$app->request->post('mobile');
            $address = Yii::$app->request->post('address');
            if ($investmentProject->send_gift_switch == 1) {
                $model->send_status = 1;
            }
            $model->send_name = $name;
            $model->send_mobile = $mobile;
            $model->send_address = $address;
            $real_pay_money = $investment_amount;
            // 判断是否使用优惠券
            // 红包
            if (!empty($ch_id = Yii::$app->request->post('ch_id'))
                && !empty($ch_model = CouponMember::find()->where(['member_id' => $this->memberId, 'id' => $ch_id, 'status' => 0])->andWhere(['>', 'stop_time', time()])->with(['coupon'])->one())
                && $investment_amount >= $ch_model->coupon->max
            ) {
                $model->ch_id = $ch_id;
                $ch_model->status = 1;
                $ch_model->save();
                // 若是使用的红包，则还需要扣除红包金额
                $real_pay_money = BcHelper::sub($investment_amount, $ch_model->coupon->number);
            }
            // 加息
            $add_income = $memberInfo->memberLevel->income;
            if (!empty($cj_id = Yii::$app->request->post('cj_id'))
                && !empty($cj_model = CouponMember::find()->where(['member_id' => $this->memberId, 'id' => $cj_id, 'status' => 0])->andWhere(['>', 'stop_time', time()])->with(['coupon'])->one())
                && $investment_amount >= $cj_model->coupon->max
            ) {
                $model->cj_id = $cj_id;
                $cj_model->status = 1;
                $cj_model->save();
                // 若使用了加息
                $add_income = BcHelper::add($add_income, $cj_model->coupon->number);
            }
            // 拿到当前用户的额外收益率
            $model->add_income = $add_income;
            // 添加订单
            $model->project_id = $id;
            $model->member_id = $this->memberId;
            $model->category = $investmentProject->category;
            $model->investment_amount = $investment_amount;
            // 判断类型 category 1按天 2按月 3按周期 4复利
            switch ($investmentProject->category) {
                case 1:
                    $model->settlement_times = $investmentProject->deadline;
                    $model->next_time = $settlementTime + (60 * 60 * 24);
                    $model->updated_at = $settlementTime + (($investmentProject->deadline) * 60 * 60 * 24);
                    break;
                case 2:
                    $model->settlement_times = BcHelper::div($investmentProject->deadline, 30, 0);
                    $model->next_time = $settlementTime + (30 * 24 * 60 * 60);
                    $model->updated_at = $settlementTime + (($investmentProject->deadline) * 60 * 60 * 24);
                    break;
                case 3:
                    $model->settlement_times = 1;
                    $model->next_time = $settlementTime + (60 * 60 * 24 * $investmentProject->deadline);
                    $model->updated_at = $settlementTime + (($investmentProject->deadline) * 60 * 60 * 24);
                    break;
                case 4:
                    $model->settlement_times = 1;
                    $model->next_time = $settlementTime + (60 * 60 * 24 * $investmentProject->deadline);
                    $model->updated_at = $settlementTime + (($investmentProject->deadline) * 60 * 60 * 24);
                    break;
                default:
                    break;
            }
            $model->save(false);
            // 更新项目内容 能投资的金额
            $investmentProject->can_investment_amount = BcHelper::sub($investmentProject->can_investment_amount, $investment_amount, 2);
            // 项目进度
            $investmentProject->schedule = BcHelper::mul(BcHelper::div(($investmentProject->all_investment_amount - $investmentProject->can_investment_amount), $investmentProject->all_investment_amount, 4));
            if ($investmentProject->schedule == 100) {
                $investmentProject->status = 0;
            }
            // 投资人数
            $investmentProject->investment_number += 1;
            $investmentProject->save(false);
            // 更新账户信息  用户经验升级
            $memberInfo->account->investment_all_money = BcHelper::add($memberInfo->account->investment_all_money, $investment_amount);
            $memberInfo->account->investment_doing_money = BcHelper::add($memberInfo->account->investment_doing_money, $investment_amount);
            // 获取真实增加经验
            $experience = BcHelper::mul($investmentProject->experience_multiple, $investment_amount);
            $memberInfo->account->experience = BcHelper::add($memberInfo->account->experience, $experience);
            $memberInfo->account->investment_number += 1;
            $memberInfo->account->save(false);
            $memberInfo->investment_status = 1;
            $memberInfo->investment_time = time();

            // 下级经验增加，判断上级是否返佣
            $buy_parents_gift = Yii::$app->debris->backendConfig('buy_parents_gift');
            $buy_gift_time_for_created_at = strtotime(Yii::$app->debris->config('buy_gift_time_for_created_at'));
            $invitees_experience_max = Yii::$app->debris->config('invitees_experience_max');
            if ($buy_gift_time_for_created_at) {
                if ($memberInfo->created_at > $buy_gift_time_for_created_at) {
                    $created_at = true;
                } else {
                    $created_at = false;
                }
            } else {
                $created_at = true;
            }
            if (
                !empty($memberInfo->pid) &&
                $buy_parents_gift>0 &&
                $memberInfo->account->experience >= $invitees_experience_max &&
                $memberInfo->return_buy_recommend == 0 &&
                $created_at
            ) {
                Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                    'member' => Member::findOne($memberInfo->pid),
                    'pay_type' => CreditsLog::COMMISSION_TYPE,
                    'num' => $buy_parents_gift,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => "【返佣】推荐用户购买达到要求，获得" . $buy_parents_gift . "元奖金",
                ]));
                // 加入佣金统计
                Statistics::updateCommissionMoney(date('Y-m-d'), $buy_parents_gift);
                $memberInfo->return_buy_recommend = 1;
            }

            // 判断项目类型 免费抽奖次数
            if ($investmentProject->lottery_number > 0) {
                $free_lottery_number = BcHelper::mul(BcHelper::div($investment_amount, $investmentProject->least_amount, 0), $investmentProject->lottery_number, 0);
                $memberInfo->free_lottery_number += $free_lottery_number;
            }

            // 如果有上级
            if (!empty($memberInfo->pid)) {
                if ($investmentProject->parent_lottery_number > 0) { // 上级赠送抽奖次数
                    $free_parent_lottery_number = BcHelper::mul(BcHelper::div($investment_amount, $investmentProject->least_amount, 0), $investmentProject->parent_lottery_number, 0);
                    $p_member = Member::findOne($memberInfo->pid);
                    $p_member->free_lottery_number += $free_parent_lottery_number;
                    $p_member->save(false);
                }

                $parent_integral = BcHelper::mul(BcHelper::div($investmentProject->parent_integral_percentage, 100, 4), $investment_amount);
                if ($parent_integral >= 1) {// 上级赠送积分
                    Yii::$app->services->memberCreditsLog->incrInt(new CreditsLogForm([
                        'member' => Member::findOne($memberInfo->pid),
                        'num' => $parent_integral,
                        'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                        'remark' => '【订单】下级购买产品获得积分',
                        'pay_type' => CreditsLog::INVESTMENT_TYPE,
                    ]));
                }

            }


            // 判断项目类型 赠送红包
            if (
                (
                    empty($investmentProject->gift_amount_time) &&
                    $investmentProject->gift_method == 1 &&
                    $investmentProject->gift_amount > 0
                )
                ||
                (
                    !empty($memberInfo->pid) &&
                    $investmentProject->gift_method == 1 &&
                    $investmentProject->gift_amount > 0 &&
                    $memberInfo->created_at > $investmentProject->gift_amount_time
                )
            ) {
                // 红包金额
                $gift_amount = BcHelper::mul(BcHelper::div($investment_amount, $investmentProject->least_amount, 0), $investmentProject->gift_amount);
                if($gift_amount>0){
                    // 添加日志更新用户余额
                    Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                        'member' => Member::findOne($this->memberId),
                        'num' => $gift_amount,
                        'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                        'remark' => '【红包】购买活动产品，获得奖金',
                        'pay_type' => CreditsLog::GIFT_TYPE,
                    ]));
                }
            }
            // 判断项目返现状态
            if ($investmentProject->return_method == 1 && $investmentProject->return_percentage > 0) {
                // 添加日志更新用户余额
                $return_amount = BcHelper::mul($investment_amount, BcHelper::div($investmentProject->return_percentage, 100, 4));
                if ($return_amount > 0) {
                    Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                        'member' => Member::findOne($this->memberId),
                        'num' => $return_amount,
                        'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                        'remark' => '【红包】购买活动产品，获得返现',
                        'pay_type' => CreditsLog::GIFT_TYPE,
                    ]));
                }
            }
            $memberInfo->save(false);

            // 更新等级
            Yii::$app->services->memberLevel->updateLevel($memberInfo);

            // 添加日志更新用户余额
            Yii::$app->services->memberCreditsLog->decrMoney(new CreditsLogForm([
                'member' => Member::findOne($this->memberId),
//                'num' => $investment_amount,
                'num' => $real_pay_money,
                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                'remark' => '【订单】购买产品：' . $investmentProject->title . '，扣除余额',
                'pay_type' => CreditsLog::INVESTMENT_TYPE,
            ]));

            // 添加积分
            $integral = BcHelper::mul(BcHelper::div($investmentProject->integral_percentage, 100, 4), $investment_amount);
            if ($integral >= 1) {
                Yii::$app->services->memberCreditsLog->incrInt(new CreditsLogForm([
                    'member' => Member::findOne($this->memberId),
                    'num' => $integral,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => '【订单】购买产品赠送积分',
                    'pay_type' => CreditsLog::INVESTMENT_TYPE,
                ]));
            }


            // 返佣处理 判断一级代理是否开启
            $commission_one = $investmentProject->commission_one;
            if ($commission_one > 0 && !empty($memberInfo->pid)) {
                // 计算一级代理推荐佣金
                $one_commission = BcHelper::mul(BcHelper::div($commission_one, 100, 4), $investment_amount);
                $one_memberInfo = Member::findOne($memberInfo->pid);
                if ($one_commission > 0) {
                    Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                        'member' => $one_memberInfo,
                        'pay_type' => CreditsLog::COMMISSION_TYPE,
                        'num' => $one_commission,
                        'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                        'remark' => "【返佣】下级购买产品，获得一级返佣",
                    ]));
                    // 加入佣金统计
                    Statistics::updateCommissionMoney(date('Y-m-d'), $one_commission);
                }

                // 返佣处理 判断二级代理是否开启
                $commission_two = $investmentProject->commission_two;
                if ($commission_two > 0 && !empty($one_memberInfo->pid)) {
                    // 计算二级代理推荐佣金
                    $two_commission = BcHelper::mul(BcHelper::div($commission_two, 100, 4), $investment_amount);
                    $two_memberInfo = Member::findOne($one_memberInfo->pid);
                    if ($two_commission > 0) {
                        Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                            'member' => $two_memberInfo,
                            'pay_type' => CreditsLog::COMMISSION_TYPE,
                            'num' => $two_commission,
                            'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                            'remark' => "【返佣】下级购买产品，获得二级返佣",
                        ]));
                        // 加入佣金统计
                        Statistics::updateCommissionMoney(date('Y-m-d'), $two_commission);
                    }
                }
            }
            // 集卡活动 投资一次获得一次卡片 先判断活动是否开启 如果打开
            $message = "认购成功！";
            if (Yii::$app->debris->config('jk_switch')) {
                // 开始抽卡 先拿到能获得的福卡数量
                if ($investmentProject->my_get_number > 0) {
                    for ($i = 0; $i < $investmentProject->my_get_number; $i++) {
                        $number = $this->get_number();
                        $moneySetting = ActivityCardSetting::findOne($number);
                        $card_model = new ActivityCardList();
                        $card_model->member_id = $this->memberId;
                        $card_model->type = 1;
                        $card_model->pid = $moneySetting['id'];
                        $card_model->remark = '【我的购买】进行了一轮购买，获得了一张' . $moneySetting['title'];
                        $card_model->save();
                    }
                }
                $message = "认购成功，获得" . $investmentProject->my_get_number . "张虎卡！";
                // 判断是否有上级，若有上级，则上级加卡
                if (!empty($memberInfo->pid)) {
                    // 先判断今天从下级获得的卡片张数
                    $today = DateHelper::today();
                    $get_card_cout = ActivityCardList::find()
                        ->where(['member_id' => $memberInfo->pid, 'type' => 2])
                        ->andWhere(['between', 'created_at', $today['start'], $today['end']])
                        ->count();
                    if ($get_card_cout < 10) {
                        // 开始抽卡 先拿到上级能获得的福卡数量
                        if ($investmentProject->one_get_number > 0) {
                            for ($i = 0; $i < $investmentProject->one_get_number; $i++) {
                                $number_one = $this->get_number();
                                $moneySetting_one = ActivityCardSetting::findOne($number_one);
                                $card_model = new ActivityCardList();
                                $card_model->member_id = $memberInfo->pid;
                                $card_model->type = 2;
                                $card_model->pid = $moneySetting_one['id'];
                                $card_model->remark = '【下级购买】下级进行了一轮购买，获得了一张' . $moneySetting_one['title'];
                                $card_model->save();
                            }
                        }
                    }
                }
            }

            // 送优惠券
            if (!empty($investmentProject->ch)) { //红包
                CouponMember::createModel($this->memberId, $investmentProject->ch->id, 1, strtotime(date("Y-m-d")), strtotime(date("Y-m-d")) + ($investmentProject->ch->valid_date * 86400));
            }
            if (!empty($investmentProject->cj)) { //加息
                CouponMember::createModel($this->memberId, $investmentProject->cj->id, 2, strtotime(date("Y-m-d")), strtotime(date("Y-m-d")) + ($investmentProject->cj->valid_date * 86400));
            }

            // 判断是否有上级，若有上级，并且项目返佣金额大于0
            if (
                !empty($memberInfo->pid)
                && $investmentProject->project_superior_rebate > 0
                && $memberInfo->created_at > $investmentProject->project_superior_rebate_time
            ) {
                // 返佣金额
                $project_superior_rebate = BcHelper::mul(BcHelper::div($investment_amount, $investmentProject->least_amount, 0), $investmentProject->project_superior_rebate);
                if ($project_superior_rebate > 0) {
                    Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                        'member' => Member::findOne($memberInfo->pid),
                        'pay_type' => CreditsLog::COMMISSION_TYPE,
                        'num' => $project_superior_rebate,
                        'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                        'remark' => "【返佣】下级购买产品，获得项目返佣奖励",
                    ]));
                    // 加入佣金统计
                    Statistics::updateCommissionMoney(date('Y-m-d'), $project_superior_rebate);
                }
            }

            // 加入投资统计
            Statistics::updateInvestment(date('Y-m-d'), date('Y-m-d', $model->updated_at), $investment_amount);

            $transaction->commit();
            return ResultHelper::json(ResultHelper::SUCCESS_CODE, $message);
        } catch (\Exception $e) {
            $transaction->rollBack();
            FileHelper::writeLog($this->getLogPath($this->action->id), $e->getMessage());
            return ResultHelper::json(ResultHelper::ERROR_CODE, "购买失败,请联系客服处理！");
        }
    }

    /**
     * @param $type
     * @return string
     */
    protected function getLogPath($type)
    {
        return Yii::getAlias('@runtime') . "/buy/" . date('Y_m_d') . '/' . $type . '.txt';
    }

    /**
     * 获取奖项id
     */
    private function get_number()
    {
        $prize_arr = ActivityCardSetting::find()->select(['id', 'proportion'])->where(['status' => 1, 'type' => 1])->asArray()->all();
        $arr = [];
        foreach ($prize_arr as $key => $val) {
            $arr[$val['id']] = $val['proportion'];
        }
        $rid = $this->get_rand($arr); //根据概率获取奖项id
        return $rid;
    }

    /**
     * 计算概率
     * @param $proArr
     * @return int|string
     */
    private function get_rand($proArr)
    {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);
        return $result;
    }

    /**
     * 投资项目详情
     * @return array|mixed|\yii\db\ActiveRecord|null
     */
    public function actionDetail()
    {
        $id = Yii::$app->request->get('id');
        if (empty($id)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "ID必须填写！");
        }
        return InvestmentBill::getModelById($id);
    }


    /**
     * 权限验证
     *
     * @param string $action 当前的方法
     * @param null $model 当前的模型类
     * @param array $params $_GET变量
     * @throws \yii\web\BadRequestHttpException
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        // 方法名称
        if (in_array($action, ['view', 'update', 'delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}