<?php

namespace backend\modules\tea\controllers;

use backend\modules\tea\forms\ZhuantouForm;
use common\enums\StatusEnum;
use common\helpers\BcHelper;
use common\helpers\DateHelper;
use common\helpers\ExcelHelper;
use common\helpers\RedisHelper;
use common\models\common\Statistics;
use common\models\forms\CreditsLogForm;
use common\models\member\Account;
use common\models\member\CreditsLog;
use common\models\member\Member;
use common\models\tea\ActivityCardList;
use common\models\tea\ActivityCardSetting;
use common\models\tea\CouponMember;
use common\models\tea\InvestmentProject;
use Yii;
use common\models\tea\InvestmentBill;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;

/**
 * InvestmentBill
 *
 * Class InvestmentBillController
 * @package backend\modules\tea\controllers
 */
class InvestmentBillController extends BaseController
{
    use Curd;

    /**
     * @var InvestmentBill
     */
    public $modelClass = InvestmentBill::class;


    /**
     * 首页
     * @return array|string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionIndex()
    {
        if (Yii::$app->request->post('hasEditable')) {
            $id = Yii::$app->request->post('editableKey');//获取ID
            Yii::$app->response->format = yii\web\Response::FORMAT_JSON;
            $model = InvestmentBill::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('InvestmentBill'));
            $post = ['InvestmentBill' => $posted];
            if ($model->load($post) && $model->save(false)) {
                $output = $model->$attribute;
            } else {
                //由于本插件不会自动捕捉model的error，所以需要放在$message中展示出来
                $message = $model->getFirstError($attribute);
            }
            return ['output' => $output, 'message' => $message];
        } else {
            $searchModel = new SearchModel([
                'model' => $this->modelClass,
                'scenario' => 'default',
                'relations' => ['investmentProject' => ['title']],
                'partialMatchAttributes' => ['investmentProject.title'], // 模糊查询
                'defaultOrder' => [
                    'created_at' => SORT_DESC
                ],
                'pageSize' => $this->pageSize
            ]);

            $dataProvider = $searchModel
                ->search(Yii::$app->request->queryParams);
            $dataProvider->query->with(['member', 'cj', 'ch']);

            $sum_investment_amount = $dataProvider->query->sum('investment_amount')??0;
            $sum_income_amount_all = $dataProvider->query->sum('income_amount_all')??0;

            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
                'sum_investment_amount' => $sum_investment_amount,
                'sum_income_amount_all' => $sum_income_amount_all,
            ]);
        }
    }

    /**
     * 导出
     */
    public function actionExport()
    {
        // 解除内存限制
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $ids = explode(",", Yii::$app->request->get('ids'));
        $model = InvestmentBill::find()
            ->where(['id' => $ids])
            ->select(['id', 'project_id', 'sn', 'send_name', 'send_mobile', 'send_address', 'send_remark'])
            ->with(['investmentProject' => function ($query) {
                $query->select(['id', 'title']);
            }])
            ->asArray()
            ->all();
        $header = [
            ['ID', 'id'],
            ['订单号', 'sn'],
            ['项目名称', 'investmentProject.title'],
            ['收件人', 'send_name'],
            ['联系电话', 'send_mobile'],
            ['收货地址', 'send_address'],
            ['快递单号', 'send_remark'],
        ];
        return ExcelHelper::exportData($model, $header);
    }

    /**
     * 导入
     * @return mixed|string
     */
    public function actionImport()
    {
        // 解除内存限制
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        if (Yii::$app->request->isPost) {
            try {
                $file = $_FILES['excelFile'];
                $data = ExcelHelper::import($file['tmp_name'], 2);
            } catch (\Exception $e) {
                return $this->message($e->getMessage(), $this->redirect(Yii::$app->request->referrer), 'error');
            }
            foreach ($data as $v) {
                $model = InvestmentBill::findOne(['sn' => $v[1]]);
                if (empty($model)) {
                    continue;
                }
                $model->send_remark = $v[6];
                $model->send_status = 2;
                $model->save();
            }
            return $this->message("操作成功！", $this->redirect(Yii::$app->request->referrer));
        }
        return $this->renderAjax($this->action->id, [
        ]);
    }


    /**
     * 转投
     * @return mixed|string
     * @throws \yii\base\ExitException
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionZhuantou()  // fixme 转移 api到此 把 $this->memberId 改成 $member_id
    {
        $member_id = Yii::$app->request->get('member_id');
        $investment_amount = Yii::$app->request->get('investment_amount');
        $form = new ZhuantouForm();
        $form->member_id = $member_id;
        $form->investment_amount = $investment_amount;
        // ajax 校验
        $this->activeFormValidate($form);
        if ($form->load(Yii::$app->request->post())) {
            $member_id = $form->member_id;
            RedisHelper::verify($member_id, $this->action->id);
            $id = $form->pid;
            $investment_amount = $form->investment_amount;
            $memberInfo = Member::find()->where(['id' => $member_id])->with(['account', 'memberLevel'])->one();
            // 判断项目状态
            if (empty($investmentProject = InvestmentProject::find()->where(['id' => $id, 'status' => StatusEnum::ENABLED])->with(['ch', 'cj'])->one())) {
                return $this->message("该产品暂停参与购买！", $this->redirect(Yii::$app->request->referrer), 'error');
            }
            // 判断用户余额
            if ($investment_amount > $memberInfo->account->user_money) {
                return $this->message("用户余额不足！", $this->redirect(Yii::$app->request->referrer), 'error');
            }
            if ($investment_amount < $investmentProject->least_amount) {
                return $this->message('起始购买金额为' . $investmentProject->least_amount . '元！', $this->redirect(Yii::$app->request->referrer), 'error');
            }
            if ($investment_amount > $investmentProject->can_investment_amount) {
                return $this->message("已超过该产品可购买额！", $this->redirect(Yii::$app->request->referrer), 'error');
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
                    && !empty($ch_model = CouponMember::find()->where(['member_id' => $member_id, 'id' => $ch_id, 'status' => 0])->andWhere(['>', 'stop_time', time()])->with(['coupon'])->one())
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
                    && !empty($cj_model = CouponMember::find()->where(['member_id' => $member_id, 'id' => $cj_id, 'status' => 0])->andWhere(['>', 'stop_time', time()])->with(['coupon'])->one())
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
                $model->member_id = $member_id;
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

                // 判断项目类型 免费抽奖次数
                if ($investmentProject->lottery_number > 0) {
                    $free_lottery_number = BcHelper::mul(BcHelper::div($investment_amount, $investmentProject->least_amount, 0), $investmentProject->lottery_number, 0);
                    $memberInfo->free_lottery_number += $free_lottery_number;
                }

                // 上级赠送抽奖次数
                if (!empty($memberInfo->pid) && $investmentProject->parent_lottery_number > 0) {
                    $free_parent_lottery_number = BcHelper::mul(BcHelper::div($investment_amount, $investmentProject->least_amount, 0), $investmentProject->parent_lottery_number, 0);
                    $p_member = Member::findOne($memberInfo->pid);
                    $p_member->free_lottery_number += $free_parent_lottery_number;
                    $p_member->save(false);
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
                    // 添加日志更新用户余额
                    Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                        'member' => Member::findOne($member_id),
                        'num' => $gift_amount,
                        'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                        'remark' => '【红包】购买活动产品，获得奖金',
                        'pay_type' => CreditsLog::GIFT_TYPE,
                    ]));
                }
                // 判断项目返现状态
                if ($investmentProject->return_method == 1 && $investmentProject->return_percentage > 0) {
                    // 添加日志更新用户余额
                    $return_amount = BcHelper::mul($investment_amount, BcHelper::div($investmentProject->return_percentage, 100, 4));
                    Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                        'member' => Member::findOne($member_id),
                        'num' => $return_amount,
                        'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                        'remark' => '【红包】购买活动产品，获得返现',
                        'pay_type' => CreditsLog::GIFT_TYPE,
                    ]));
                }
                $memberInfo->save(false);

                // 更新等级
                Yii::$app->services->memberLevel->updateLevel($memberInfo);

                // 添加日志更新用户余额
                Yii::$app->services->memberCreditsLog->decrMoney(new CreditsLogForm([
                    'member' => Member::findOne($member_id),
//                'num' => $investment_amount,
                    'num' => $real_pay_money,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => '【订单】购买产品：' . $investmentProject->title . '，扣除余额',
                    'pay_type' => CreditsLog::INVESTMENT_TYPE,
                ]));

                // 添加积分
                $integral = BcHelper::mul(BcHelper::div($investmentProject->integral_percentage, 100, 4), $investment_amount);
                if ($integral > 0) {
                    Yii::$app->services->memberCreditsLog->incrInt(new CreditsLogForm([
                        'member' => Member::findOne($member_id),
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
                    Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                        'member' => $one_memberInfo,
                        'pay_type' => CreditsLog::COMMISSION_TYPE,
                        'num' => $one_commission,
                        'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                        'remark' => "【返佣】下级购买产品，获得一级返佣",
                    ]));
                    // 加入佣金统计
                    Statistics::updateCommissionMoney(date('Y-m-d'), $one_commission);
                    // 返佣处理 判断二级代理是否开启
                    $commission_two = $investmentProject->commission_two;
                    if ($commission_two > 0 && !empty($one_memberInfo->pid)) {
                        // 计算二级代理推荐佣金
                        $two_commission = BcHelper::mul(BcHelper::div($commission_two, 100, 4), $investment_amount);
                        $two_memberInfo = Member::findOne($one_memberInfo->pid);
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
                // 集卡活动 投资一次获得一次卡片 先判断活动是否开启 如果打开
                $message = "购买成功！";
                if (Yii::$app->debris->config('jk_switch')) {
                    // 开始抽卡 先拿到能获得的福卡数量
                    if ($investmentProject->my_get_number > 0) {
                        for ($i = 0; $i < $investmentProject->my_get_number; $i++) {
                            $number = $this->get_number();
                            $moneySetting = ActivityCardSetting::findOne($number);
                            $card_model = new ActivityCardList();
                            $card_model->member_id = $member_id;
                            $card_model->type = 1;
                            $card_model->pid = $moneySetting['id'];
                            $card_model->remark = '【我的购买】进行了一轮购买，获得了一张' . $moneySetting['title'];
                            $card_model->save();
                        }
                    }
                    $message = "购买成功，获得" . $investmentProject->my_get_number . "张虎卡！";
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
                    CouponMember::createModel($member_id, $investmentProject->ch->id, 1, strtotime(date("Y-m-d")), strtotime(date("Y-m-d")) + ($investmentProject->ch->valid_date * 86400));
                }
                if (!empty($investmentProject->cj)) { //加息
                    CouponMember::createModel($member_id, $investmentProject->cj->id, 2, strtotime(date("Y-m-d")), strtotime(date("Y-m-d")) + ($investmentProject->cj->valid_date * 86400));
                }

                // 判断是否有上级，若有上级，并且项目返佣金额大于0
                if (
                    !empty($memberInfo->pid)
                    && $investmentProject->project_superior_rebate > 0
                    && $memberInfo->created_at > $investmentProject->project_superior_rebate_time
                ) {
                    // 返佣金额
                    $project_superior_rebate = BcHelper::mul(BcHelper::div($investment_amount, $investmentProject->least_amount, 0), $investmentProject->project_superior_rebate);
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

                // 加入投资统计
                Statistics::updateInvestment(date('Y-m-d'), date('Y-m-d', $model->updated_at), $investment_amount);

                $transaction->commit();
                return $this->message("转购成功！", $this->redirect(Yii::$app->request->referrer));
            } catch (\Exception $e) {
                $transaction->rollBack();
                return $this->message($this->getError($model), $this->redirect(Yii::$app->request->referrer), 'error');
            }
        }
        return $this->renderAjax($this->action->id, [
            'model' => $form,
        ]);
    }

    /**
     * 结算收益
     * @param $id
     * @return mixed
     * @throws \yii\db\Exception
     * @author 哈哈
     */
    public function actionJiesuan($id)
    {
        $model = InvestmentBill::find()
            ->where(['id' => $id])
            ->andWhere(['>', 'income_amount', 0])
            ->andWhere(['<>', 'status', 4])
            ->with('investmentProject')
            ->with('member')
            ->with('account')
            ->one();
        if (empty($model)) {
            return $this->message('当前订单项没有收益可结算或者已停止结算', $this->redirect(Yii::$app->request->referrer), 'error');
        } else {
            $this->jiesuan($model);
            return $this->message('操作成功', $this->redirect(Yii::$app->request->referrer));
        }
    }

    /**
     * 批量结算
     * @return mixed
     * @throws \yii\db\Exception
     * @author 哈哈
     */
    public function actionPassAll()
    {
        $ids = explode(',', Yii::$app->request->get('ids'));
        if (!empty($ids)) {
            // 先判断所选订单内是否已经被别人处理过
            foreach ($ids as $v1) {
                $model = InvestmentBill::find()
                    ->where(['id' => $v1])
                    ->andWhere(['>', 'income_amount', 0])
                    ->andWhere(['<>', 'status', 4])
                    ->with('investmentProject')
                    ->with('member')
                    ->with('account')
                    ->one();
                if (empty($model)) {
                    return $this->message('当前订单项没有收益可结算或者已停止结算', $this->redirect(Yii::$app->request->referrer), 'error');
                } else {
                    $this->jiesuan($model);
                }
            }
            return $this->message('操作成功', $this->redirect(Yii::$app->request->referrer));
        } else {
            return $this->message('未选择订单', $this->redirect(Yii::$app->request->referrer), 'error');
        }
    }

    /**
     * 项目到期
     * @param $id
     * @return mixed
     */
    public function actionDaoqi($id)
    {
        $model = InvestmentBill::find()
            ->where(['id' => $id])
            ->andWhere(['<>', 'status', 3])
            ->one();
        if (empty($model)) {
            return $this->message('没有产品可到期', $this->redirect(Yii::$app->request->referrer), 'error');
        } else {
            $model->status = 3;
            $model->save(false);
            //操作用户
            $memberInfo = Member::find()->where(['id' => $model->member_id])->with(['account'])->one();
            // 减掉再投金额
            $memberInfo->account->investment_doing_money -= $model->investment_amount;
            $memberInfo->account->save(false);
            if ($memberInfo->account->investment_doing_money == 0) {
                // 投资状态改成2未投
                $memberInfo->investment_status = 2;
                $memberInfo->save(false);
            }
            // 退本
            Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                'member' => Member::findOne($model->member_id),
                'pay_type' => CreditsLog::FB_TYPE,
                'num' => $model->investment_amount,
                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                'remark' => "【退本】购买产品：" . $model->investmentProject->title . "，周期结束退还本金" . $model->investment_amount . "元",
                'map_id' => $model->id,
            ]));
            return $this->message('操作成功', $this->redirect(Yii::$app->request->referrer));
        }
    }

    /**
     * 停止结算
     * @param $id
     * @return mixed
     */
    public function actionTingjie($id)
    {
        $model = InvestmentBill::find()
            ->where(['id' => $id])
            ->andWhere(['<>', 'status', 4])
            ->with(['investmentProject'])
            ->one();
        if (empty($model)) {
            return $this->message('该产品已被停止结算', $this->redirect(Yii::$app->request->referrer), 'error');
        } else {
            $model->status = 4;
            $model->income_amount = 0;
            $model->save(false);
            //操作用户
            $memberInfo = Member::find()->where(['id' => $model->member_id])->with(['account'])->one();
            // 减掉再投金额
            $memberInfo->account->investment_doing_money -= $model->investment_amount;
            $memberInfo->account->save(false);
            if ($memberInfo->account->investment_doing_money == 0) {
                // 投资状态改成2未投
                $memberInfo->investment_status = 2;
                $memberInfo->save(false);
            }
            return $this->message('操作成功', $this->redirect(Yii::$app->request->referrer));
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

    /**
     * 编辑/创建
     * @return mixed|string
     * @throws \yii\base\ExitException
     */
    public function actionKuaidi()
    {
        $id = Yii::$app->request->get('id');
        $model = $this->findModel($id);

        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            if (!empty($model->send_remark)) {
                $model->send_status = 2;
            }
            return $model->save()
                ? $this->message("操作成功", $this->redirect(Yii::$app->request->referrer))
                : $this->message($this->getError($model), $this->redirect(Yii::$app->request->referrer), 'error');
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }
}
