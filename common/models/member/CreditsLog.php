<?php

namespace common\models\member;

use common\enums\StatusEnum;
use Yii;

/**
 * This is the model class for table "{{%member_credits_log}}".
 *
 * @property int $id
 * @property string $merchant_id 商户id
 * @property string $member_id 用户id
 * @property string $app_id 应用
 * @property string $addons_name 插件
 * @property int $pay_type 支付类型
 * @property string $credit_type 变动类型[integral:积分;money:余额]
 * @property string $credit_group 变动的组别
 * @property double $old_num 之前的数据
 * @property double $new_num 变动后的数据
 * @property double $num 变动的数据
 * @property string $remark 备注
 * @property string $ip ip地址
 * @property string $map_id 关联id
 * @property int $status 状态[-1:删除;0:禁用;1启用]
 * @property string $created_at 创建时间
 * @property string $updated_at 修改时间
 */
//class CreditsLog extends \common\models\base\BaseModel
class CreditsLog extends \yii\db\ActiveRecord
{
    // 金额类型
    const CREDIT_TYPE_USER_MONEY = 'user_money';
    const CREDIT_TYPE_GIVE_MONEY = 'give_money';
    const CREDIT_TYPE_CONSUME_MONEY = 'consume_money';
    const CREDIT_TYPE_CAN_WITHDRAW_MONEY = 'can_withdraw_money';

    // 积分类型
    const CREDIT_TYPE_USER_INTEGRAL = 'user_integral';
    const CREDIT_TYPE_GIVE_INTEGRAL = 'give_integral';

    const CREDIT_GROUP_MANAGER = 'manager';
    const CREDIT_GROUP_MEMBER = 'member';

    /**
     * 变动组别
     */
    public static $creditGroupExplain = [
        self::CREDIT_GROUP_MANAGER => '管理员',
        self::CREDIT_GROUP_MEMBER => '会员',
    ];

    public static $creditTypeExplain = [
        self::CREDIT_TYPE_USER_MONEY => '余额钱包',
//        self::CREDIT_TYPE_USER_INTEGRAL=>'积分',
//        self::CREDIT_TYPE_CAN_WITHDRAW_MONEY => '余额钱包',
    ];

    const RECHARGE_PAY_TYPE = 1;
    const WITHDRAW_PAY_TYPE = 2;
    const REGISTER_TYPE = 3;
    const SIGN_TYPE = 4;
    const LOTTERY_TYPE = 5;
    const INVESTMENT_TYPE = 6;
    const INCOME_TYPE = 7;
    const COMMISSION_TYPE = 8;
    const GIFT_TYPE = 9;
    const EXCHANGE_TYPE = 10;
    const JK_TYPE = 11;
    const Q_A = 12;
    const FB_TYPE = 13;
    const BUY_LEVEL_TYPE = 14;
    const BUY_SHORT_PLAYS_TYPE = 15;
    const SEND_SHORT_PLAYS_TYPE = 16;
    const PROMOTION_TYPE = 17;
    const TASK_TYPE = 18;
    /**
     * 类别
     */
    public static $PayTypeExplain = [
//        self::RECHARGE_PAY_TYPE => '充值',
        self::WITHDRAW_PAY_TYPE => '提现',
        self::REGISTER_TYPE => '注册',
//        self::SIGN_TYPE => '签到',
//        self::LOTTERY_TYPE => '摇奖',
//        self::INVESTMENT_TYPE => '打卡',
//        self::INCOME_TYPE => '收益',
//        self::COMMISSION_TYPE => '返佣',
//        self::GIFT_TYPE => '红包',
//        self::EXCHANGE_TYPE => '兑换',
//        self::JK_TYPE => '集卡',
//        self::Q_A => '碳问答',
//        self::FB_TYPE => '退本',
//        self::BUY_LEVEL_TYPE => '购买会员',
//        self::BUY_SHORT_PLAYS_TYPE => '购买授权',
//        self::SEND_SHORT_PLAYS_TYPE => '发货授权',
//        self::PROMOTION_TYPE => '推广',
        self::TASK_TYPE => '任务',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%member_credits_log}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pay_type', 'merchant_id', 'member_id', 'map_id', 'status', 'created_at', 'updated_at'], 'integer'],
            [['old_num', 'new_num', 'num'], 'number'],
            [['credit_type', 'credit_group'], 'string', 'max' => 30],
            [['ip'], 'string', 'max' => 255],
            [['remark'], 'string', 'max' => 200],
            [['app_id'], 'string', 'max' => 50],
            [['addons_name'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'merchant_id' => '商户',
            'member_id' => '用户',
            'pay_type' => '账变类型',
            'map_id' => '关联id',
            'ip' => 'ip地址',
            'credit_type' => '变动类型',
            'app_id' => '操作类型',
            'credit_group' => '操作人',
            'old_num' => '变更之前',
            'new_num' => '变更后',
            'num' => '变更数量',
            'remark' => '备注',
            'status' => '状态',
            'created_at' => '创建时间',
            'updated_at' => '修改时间',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMember()
    {
        return $this->hasOne(Member::class, ['id' => 'member_id']);
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if ($this->isNewRecord) {
            $this->app_id = Yii::$app->id;
            $this->addons_name = Yii::$app->params['addon']['name'] ?? '';
        }

        return parent::beforeSave($insert);
    }

    /**
     * @param $member_id
     * @param $page
     * @param $pay_type
     * @param $credit_type
     * @param $pages_size
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getLists($member_id, $page, $pay_type, $credit_type, $pages_size)
    {
        $pages = ($page - 1) * $pages_size;
        if (!empty($pay_type)) {
            $pay_type_sql = " AND `pay_type` = $pay_type";
        } else {
            $pay_type_sql = "";
        }
        if (!empty($credit_type)) {
            $credit_type_sql = ' AND `credit_type` = ' . '"' . $credit_type . '"';
        } else {
            $credit_type_sql = "";
        }
        $inner = "(SELECT `id` FROM " . self::tableName() . " b WHERE (`member_id` = $member_id" . $pay_type_sql . $credit_type_sql . " AND `status` = " . StatusEnum::ENABLED . ") ORDER BY `id` DESC LIMIT " . $pages . " , " . "$pages_size" . ") b using (`id`)";

        return self::find()
            ->select(['id', 'credit_type', 'num', 'remark', 'FROM_UNIXTIME(`created_at`,\'%Y-%m-%d %H:%i:%s\') as created_at', 'pay_type'])
            ->innerJoin($inner)
            ->orderBy(['id' => SORT_DESC])
            ->asArray()
            ->all();
    }
}
