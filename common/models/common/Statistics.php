<?php

namespace common\models\common;

use common\helpers\BcHelper;
use common\helpers\DateHelper;
use common\models\backend\Member;
use common\models\member\CreditsLog;
use common\models\member\RechargeBill;
use common\models\member\WithdrawBill;
use Yii;

/**
 * This is the model class for table "i_statistics".
 *
 * @property int $id
 * @property int $register_member 今日注册人数
 * @property int $sign_member 今日签到人数
 * @property string $withdraw_money 今日提现金额
 * @property string $recharge_money 今日充值金额
 * @property string $commission_money 今日发放佣金
 * @property int $new_investment_number 今日新增投资数
 * @property string $new_investment_money 今日新增投资额
 * @property int $stop_investment_number 今日到期投资数
 * @property string $stop_investment_money 今日到期投资额
 * @property string $income_money 今日发放收益
 * @property string $date 日期
 * @property int $created_at 截止统计时间
 * @property int $withdraw_number
 * @property int $recharge_number
 * @property string $gift_money
 * @property string $buy_money
 * @property int $member_id
 * @property int $get_task_number
 * @property int $over_task_number
 */
class Statistics extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'base_statistics';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['register_member', 'sign_member', 'new_investment_number', 'stop_investment_number', 'created_at', 'withdraw_number', 'recharge_number', 'member_id', 'get_task_number', 'over_task_number'], 'integer'],
            [['withdraw_money', 'recharge_money', 'commission_money', 'new_investment_money', 'stop_investment_money', 'income_money', 'gift_money', 'buy_money'], 'number'],
            [['date'], 'required'],
            [['date'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'register_member' => '今日注册人数',
            'sign_member' => '今日签到人数',
            'withdraw_money' => '今日提现金额',
            'recharge_money' => '今日充值金额',
            'commission_money' => '今日发放佣金',
            'new_investment_number' => '今日新增购买数',
            'new_investment_money' => '今日新增购买额',
            'stop_investment_number' => '今日到期购买数',
            'stop_investment_money' => '今日到期购买额',
            'date' => '日期',
            'created_at' => '截止统计时间',
            'income_money' => '今日发放收益',
            'withdraw_number' => '提现人数',
            'recharge_number' => '充值人数',
            'gift_money' => '赠送礼金',
            'buy_money' => '销售额',
            'member_id' => '代理ID',
            'get_task_number' => '领取任务数',
            'over_task_number' => '完成任务数',
        ];
    }

    /**
     * 更新今日注册人数
     * @param $date
     * @param $b_id
     * @return bool
     * @author 哈哈
     */
    public static function updateRegisterMember($date, $b_id)
    {
        $model = self::find()->where(['date' => $date, 'member_id' => $b_id])->one();
        if (empty($model)) {
            $model = new self();
            $model->date = date("Y-m-d");
            $model->member_id = $b_id;
        }
        $model->register_member += 1;
        $model->created_at = time();
        $model->save(false);
        return true;
    }

    /**
     * 更新今日签到人数
     * @param $date
     * @return bool
     * @author 哈哈
     */
    public static function updateSignMember($date)
    {
        $model = self::find()->where(['date' => $date])->one();
        if (empty($model)) {
            $model = new self();
            $model->date = date("Y-m-d");
        }
        $model->sign_member += 1;
        $model->created_at = time();
        $model->save(false);
        return true;
    }

    /**
     * 更新今日新增投资数/金额
     * @param $date
     * @param $stop_date
     * @param $investment_money
     * @return bool
     * @author 哈哈
     */
    public static function updateInvestment($date, $stop_date, $investment_money)
    {
        // 今日新增投资数/金额
        $model = self::find()->where(['date' => $date])->one();
        if (empty($model)) {
            $model = new self();
            $model->date = date("Y-m-d");
        }
        $model->new_investment_number += 1;
        $model->new_investment_money = BcHelper::add($model->new_investment_money, $investment_money, 2);
        $model->created_at = time();
        $model->save(false);
        return true;
    }

    /**
     * 更新今日新增投资数/金额和到期投资数/金额
     * @param $date
     * @param $stop_date
     * @param $investment_money
     * @return bool
     * @author 哈哈
     */
    public static function updateInvestmentOld($date, $stop_date, $investment_money)
    {
        // 今日新增投资数/金额
        $model = self::find()->where(['date' => $date])->one();
        if (empty($model)) {
            $model = new self();
            $model->date = date("Y-m-d");
        }
        $model->new_investment_number += 1;
        $model->new_investment_money = BcHelper::add($model->new_investment_money, $investment_money, 2);
        $model->created_at = time();
        $model->save(false);
        // 到期投资数/金额
        $stop_model = self::find()->where(['date' => $stop_date])->one();
        if (empty($stop_model)) {
            $stop_model = new self();
            $stop_model->date = date($stop_date);
        }
        $stop_model->stop_investment_number += 1;
        $stop_model->stop_investment_money = BcHelper::add($stop_model->stop_investment_money, $investment_money, 2);
        $stop_model->created_at = time();
        $stop_model->save(false);
        return true;
    }

    /**
     * 更新今日提现金额
     * @param $date
     * @param $withdraw_money
     * @param $b_id
     * @return bool
     * @author 哈哈
     */
    public static function updateWithdrawMoney($date, $withdraw_money, $member_id, $b_id)
    {
        $model = self::find()->where(['date' => $date, 'member_id' => $b_id])->one();
        if (empty($model)) {
            $model = new self();
            $model->date = date("Y-m-d");
            $model->member_id = $b_id;
        }
        $model->withdraw_money = BcHelper::add($model->withdraw_money, $withdraw_money, 2);
        $model->created_at = time();
        // 判断今日是否提现
        $today = DateHelper::today();
        if (WithdrawBill::find()->where(['member_id' => $member_id, 'status' => 1])->andWhere(['between', 'created_at', $today['start'], $today['end']])->count() == 0) {
            $model->withdraw_number += 1;
            $model->created_at = time();
        }
        $model->save(false);
        return true;
    }

    /**
     * 更新今日充值金额
     * @param $date
     * @param $recharge_money
     * @param $b_id
     * @return bool
     * @author 哈哈
     */
    public static function updateRechargeMoney($date, $recharge_money, $member_id, $b_id)
    {
        $model = self::find()->where(['date' => $date, 'member_id' => $b_id])->one();
        if (empty($model)) {
            $model = new self();
            $model->date = date("Y-m-d");
            $model->member_id = $b_id;
        }
        $model->recharge_money = BcHelper::add($model->recharge_money, $recharge_money, 2);
        $model->created_at = time();

        // 判断今日是否充值
        $today = DateHelper::today();
        $count = CreditsLog::find()
            ->where(['member_id' => $member_id, 'pay_type' => CreditsLog::RECHARGE_PAY_TYPE, 'credit_type' => CreditsLog::CREDIT_TYPE_USER_MONEY])
            ->andWhere(['>', 'num', 0])
            ->andWhere(['between', 'created_at', $today['start'], $today['end']])
            ->count();
        if ($count == 1) {
            $model->recharge_number += 1;
            $model->created_at = time();
        }
        $model->save(false);

        return true;
    }

    /**
     * 更新今日赠送金额
     * @param $date
     * @param $gift_money
     * @return bool
     * @author 哈哈
     */
    public static function updateGiftMoney($date, $gift_money)
    {
        $model = self::find()->where(['date' => $date])->one();
        if (empty($model)) {
            $model = new self();
            $model->date = date("Y-m-d");
        }
        $model->gift_money = BcHelper::add($model->gift_money, $gift_money);
        $model->created_at = time();
        $model->save(false);
        return true;
    }

    /**
     * 销售额
     * @param $date
     * @param $buy_money
     * @param $b_id
     * @return bool
     * @author 哈哈
     */
    public static function updateBuyMoney($date, $buy_money, $b_id)
    {
        $model = self::find()->where(['date' => $date, 'member_id' => $b_id])->one();
        if (empty($model)) {
            $model = new self();
            $model->date = date("Y-m-d");
            $model->member_id = $b_id;
        }
        $model->buy_money = BcHelper::add($model->buy_money, $buy_money);
        $model->created_at = time();
        $model->save(false);
        return true;
    }

    /**
     * 更新今日发放佣金金额
     * @param $date
     * @param $commission_money
     * @param $b_id
     * @return bool
     * @author 哈哈
     */
    public static function updateCommissionMoney($date, $commission_money, $b_id)
    {
        $model = self::find()->where(['date' => $date, 'member_id' => $b_id])->one();
        if (empty($model)) {
            $model = new self();
            $model->date = date("Y-m-d");
            $model->member_id = $b_id;
        }
        $model->commission_money = BcHelper::add($model->commission_money, $commission_money, 2);
        $model->created_at = time();
        $model->save(false);
        return true;
    }


    /**
     * 更新今日发放收益金额
     * @param $date
     * @param $b_id
     * @param $$member_id
     * @return bool
     * @author 哈哈
     */
    public static function updateIncomeMoney($date, $income_money, $b_id)
    {
        $model = self::find()->where(['date' => $date, 'member_id' => $b_id])->one();
        if (empty($model)) {
            $model = new self();
            $model->date = date("Y-m-d");
            $model->member_id = $b_id;
        }
        $model->income_money = BcHelper::add($model->income_money, $income_money);
        $model->created_at = time();
        $model->save(false);
        return true;
    }

    /**
     * @param $from_date
     * @param $to_date
     * @param $member_id
     * @return array|\yii\db\ActiveRecord[]
     * @author 哈哈
     */
    public static function findBetweenByDate($from_date, $to_date, $member_id)
    {
        if ($member_id == "0") {
            $member_id_where = [];
        } else {
            $member_id_where = ['member_id' => $member_id];
        }
        return self::find()
            ->where(['between', 'date', $from_date, $to_date])
            ->andFilterWhere($member_id_where)
            ->with(['manager' => function ($query) {
                $query->select(['id', 'username']);
            }])
            ->orderBy('date desc')
            ->asArray()
            ->all();
    }

    /**
     * 根据日期获取
     * @param $date
     * @return array|null|\yii\db\ActiveRecord
     * @author 哈哈
     */
    public static function findByDate($date, $member_id_where)
    {
        return self::find()
            ->select([
                'sum(`register_member`) as register_member',
                'sum(`withdraw_money`) as withdraw_money',
                'sum(`recharge_money`) as recharge_money',
                'sum(`buy_money`) as buy_money',
                'sum(`commission_money`) as commission_money',
                'sum(`income_money`) as income_money',
                'sum(`recharge_number`) as recharge_number',
                'sum(`withdraw_number`) as withdraw_number',
            ])
            ->where(['date' => $date])
            ->andFilterWhere($member_id_where)
            ->asArray()
            ->one();
    }

    /**
     * 本月所有累积
     * @return array|null|\yii\db\ActiveRecord
     * @author 哈哈
     */
    public static function findByMonth($member_id_where)
    {
        $thisMonth = DateHelper::thisMonth();
        return self::find()
            ->select([
                'sum(`register_member`) as register_member',
                'sum(`withdraw_money`) as withdraw_money',
                'sum(`recharge_money`) as recharge_money',
                'sum(`buy_money`) as buy_money',
                'sum(`commission_money`) as commission_money',
                'sum(`income_money`) as income_money',
            ])
            ->where(['between', 'date', date('Y-m-d', $thisMonth['start']), date('Y-m-d', $thisMonth['end'])])
            ->andFilterWhere($member_id_where)
            ->asArray()
            ->one();
    }


    /**
     * 获取所有累积
     * @return array|null|\yii\db\ActiveRecord
     * @author 哈哈
     */
    public static function findByDateAll($member_id_where)
    {
        return self::find()
            ->select([
                'sum(`register_member`) as register_member',
                'sum(`withdraw_money`) as withdraw_money',
                'sum(`recharge_money`) as recharge_money',
                'sum(`buy_money`) as buy_money',
                'sum(`commission_money`) as commission_money',
                'sum(`income_money`) as income_money',
            ])
            ->andFilterWhere($member_id_where)
            ->asArray()
            ->one();
    }

    public function getManager()
    {
        return $this->hasOne(Member::class, ['id' => 'member_id']);
    }


    /**
     * 更新今日完成任务
     * @param $date
     * @param $money
     * @param $b_id
     * @return bool
     * @author 哈哈
     */
    public static function updateOverTask($date, $money, $b_id)
    {
        $model = self::find()->where(['date' => $date, 'member_id' => $b_id])->one();
        if (empty($model)) {
            $model = new self();
            $model->date = date("Y-m-d");
            $model->member_id = $b_id;
        }
        $model->over_task_number += 1;
        $model->commission_money = BcHelper::add($model->commission_money, $money);
        $model->created_at = time();
        $model->save(false);

        return true;
    }

    /**
     * 更新今日领取任务
     * @param $date
     * @param $money
     * @param $b_id
     * @return bool
     * @author 哈哈
     */
    public static function updateGetTask($date, $b_id)
    {
        $model = self::find()->where(['date' => $date, 'member_id' => $b_id])->one();
        if (empty($model)) {
            $model = new self();
            $model->date = date("Y-m-d");
            $model->member_id = $b_id;
        }
        $model->get_task_number += 1;
        $model->created_at = time();
        $model->save(false);

        return true;
    }
}
