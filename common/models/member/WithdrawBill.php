<?php

namespace common\models\member;

use common\helpers\BcHelper;
use common\helpers\CommonPluginHelper;
use common\models\common\Statistics;
use common\models\forms\CreditsLogForm;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\Json;
use yii\web\UnprocessableEntityHttpException;

/**
 * This is the model class for table "base_withdraw_bill".
 *
 * @property int $id
 * @property int $member_id 会员ID
 * @property string $sn 订单号
 * @property string $withdraw_money 提现金额
 * @property string $handling_fees
 * @property string $type 提现类型
 * @property int $created_at 提现时间
 * @property int $updated_at 审核时间
 * @property int $status 状态(0未审核,1通过,2拒绝,3取消)
 * @property string $remark 备注
 * @property int $card_id 银行卡ID
 * @property string $real_withdraw_money
 * @property int $pay_type
 */
class WithdrawBill extends \yii\db\ActiveRecord
{

    public static $statusExplain = [
        0 => "待审核",
        1 => "已通过",
        2 => "已拒绝",
        3 => "已取消",
        4 => "待回调",
    ];
    const WECHAT_ACCOUNT_URL = 1;
    const WECHAT_ACCOUNT = 2;
    const ALIPAY_ACCOUNT = 3;
    const ALIPAY_ACCOUNT_URL = 4;
    const BANK_CARD = 5;
    const USDT_TRC20 = 6;
    const PLATFORM_ACCOUNT = 7;
    const GCASH_ACCOUNT = 8;
    const MAYA_ACCOUNT = 9;

    public static $typeExplain = [
//        self::WECHAT_ACCOUNT_URL => "微信收款码",
//        self::WECHAT_ACCOUNT => "微信红包",
//        self::ALIPAY_ACCOUNT => "转账支付宝",
//        self::ALIPAY_ACCOUNT_URL => "支付宝收款码",
        self::BANK_CARD => "转账银行卡",
//        self::USDT_TRC20 => "USDT-TRC20",
        self::PLATFORM_ACCOUNT => "Fastplay平台账号",
        self::GCASH_ACCOUNT => "Gcash钱包",
        self::MAYA_ACCOUNT => "Maya账户",
    ];

    public static $payTypeExplain = [
        1 => "盛利代付",
    ];

    public static $typeMatchExplain = [
        self::WECHAT_ACCOUNT_URL => "wechat_account_url",
        self::WECHAT_ACCOUNT => "wechat_account",
        self::ALIPAY_ACCOUNT => "alipay_account",
        self::ALIPAY_ACCOUNT_URL => "alipay_account_url",
        self::BANK_CARD => "bank_card",
        self::USDT_TRC20 => "usdt_link",
        self::PLATFORM_ACCOUNT => "platform_account",
        self::GCASH_ACCOUNT => "gcash_name",
        self::MAYA_ACCOUNT => "maya_name",
    ];

    public static $statusColorExplain = [
        0 => "info",
        1 => "success",
        2 => "primary",
        3 => "warning",
        4 => "danger",
    ];

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'base_withdraw_bill';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['withdraw_money', 'type'], 'required'],
            [['member_id', 'created_at', 'updated_at', 'status', 'card_id', 'pay_type', 'id'], 'integer'],
            [['sn'], 'string', 'max' => 50],
            [['type'], 'string', 'max' => 30],
            [['remark'], 'string', 'max' => 255],
            [['type'], 'in', 'range' => array_keys(self::$typeExplain)],
            [['type'], 'verifyType'],
            [['real_withdraw_money', 'handling_fees', 'withdraw_money'], 'number', 'numberPattern' => '/^\d+(.\d{1,2})?$/', 'message' => '小数点后位数不能大于俩位'],
        ];
    }

    /**
     * 验证提现类型
     * @param $attribute
     * @param $params
     * @throws UnprocessableEntityHttpException
     */
    public function verifyType($attribute, $params)
    {
        if (!$this->hasErrors()) {
            // 判断提现类型
            $withdraw_method = Json::decode(Yii::$app->debris->config('withdraw_method'));
            if (!in_array($this->type, $withdraw_method)) {
                throw new UnprocessableEntityHttpException('该提现方式已暂停使用，请选择其他的提现方式！');
            }
            if ($this->type == 5) {
                if (!MemberCard::find()->where(['member_id' => Yii::$app->user->identity['member_id']])->exists()) {
                    throw new UnprocessableEntityHttpException('您暂未绑定银行卡，请绑定后再试！');
                }
            } else {
                if (!Account::find()->where(['member_id' => Yii::$app->user->identity['member_id']])->andWhere(['<>', self::$typeMatchExplain[$this->type], ''])->exists()) {
                    throw new UnprocessableEntityHttpException('您所提交的提现类型暂未绑定，请绑定后再试！');
                }
            }

        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'member_id' => '会员ID',
            'sn' => '订单号',
            'withdraw_money' => '提现金额',
            'type' => '提现类型',
            'created_at' => '提现时间',
            'updated_at' => '审核时间',
            'status' => '状态',
            'remark' => '备注',
            'card_id' => '银行卡',
            'real_withdraw_money' => '汇款金额',
            'pay_type' => '代付平台',
            'handling_fees' => '手续费',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMember()
    {
        return $this->hasOne(Member::class, ['id' => 'member_id']);
    }

    public function getAccount()
    {
        return $this->hasOne(Account::class, ['member_id' => 'member_id']);
    }

    public function getCard()
    {
        return $this->hasOne(MemberCard::class, ['id' => 'card_id']);
    }


    /**
     * @param bool $insert
     * @return bool
     * @throws \yii\base\Exception
     */
    public function beforeSave($insert)
    {
        // 如果是新增
        if ($this->isNewRecord) {
            $this->member_id = Yii::$app->user->identity['member_id'];
            $member = Member::find()->where(['id' => $this->member_id])->with(['memberLevel'])->one();
            $this->sn = CommonPluginHelper::getSn($this->member_id);
            $this->handling_fees = BcHelper::mul($this->withdraw_money, BcHelper::div($member->sellerLevel->handling_fees_percentage, 100, 4));
            if ($this->type == self::USDT_TRC20) {
                $real_withdraw_money = BcHelper::mul(BcHelper::sub($this->withdraw_money, $this->handling_fees), Yii::$app->debris->backendConfig('usdt_exchange_rate_withdraw'));
            } else {
                $real_withdraw_money = BcHelper::mul(BcHelper::sub($this->withdraw_money, $this->handling_fees), Yii::$app->debris->backendConfig('platform_exchange_rate'));
            }
            $this->real_withdraw_money = $real_withdraw_money;
            // 如果是新增 则扣除账户余额
            if ($this->status == 0 && $this->withdraw_money > 0) {
                Yii::$app->services->memberCreditsLog->decrMoney(new CreditsLogForm([
                    'member' => $member,
                    'num' => $this->withdraw_money,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => '【系统】提现扣除余额',
                    'pay_type' => CreditsLog::WITHDRAW_PAY_TYPE,
                    'increase' => 0,
                ]));
            }
        } else {
            // 如果是修改拒绝 则增加账户余额
            if ($this->isAttributeChanged('status') && $this->status == 2 && $this->withdraw_money > 0) {
                Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                    'member' => Member::findOne($this->member_id),
                    'num' => $this->withdraw_money,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => '【系统】提现被拒返款',
                    'pay_type' => CreditsLog::WITHDRAW_PAY_TYPE,
                    'increase' => 0,
                ]));
            } elseif ($this->isAttributeChanged('status') && $this->status == 1) {
                // 提现成功 用户扣除本金
                $member = Member::findOne($this->member_id);
                $member->principal = BcHelper::sub($member->principal, $this->withdraw_money);
                $member->withdraw_money = BcHelper::add($member->withdraw_money, $this->withdraw_money);
                $member->save(false);
                // 加入统计表
                if ($member['type'] == 1) {
                    // 加入统计表 获取最上级用户ID
                    $first_member = Member::getParentsFirst($member);
                    $b_id = $first_member['b_id'] ?? 0;
                    Statistics::updateWithdrawMoney(date("Y-m-d"), $this->withdraw_money, $this->member_id, $b_id);
                }
            }
        }
        return parent::beforeSave($insert);
    }
}
