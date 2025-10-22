<?php

namespace common\models\member;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%member_account}}".
 *
 * @property int $id
 * @property int $merchant_id 商户id
 * @property int $member_id 用户id
 * @property double $user_money 当前余额
 * @property double $accumulate_money 累计余额
 * @property double $give_money 累计赠送余额
 * @property double $consume_money 累计消费金额
 * @property double $frozen_money 冻结金额
 * @property int $user_integral 当前积分
 * @property int $accumulate_integral 累计积分
 * @property int $give_integral 累计赠送积分
 * @property string $consume_integral 累计消费积分
 * @property int $frozen_integral 冻结积分
 * @property string $wechat_account_url 微信收款码
 * @property string $alipay_account_url 支付宝收款码
 * @property double $experience 经验值
 * @property double $investment_all_money 投资总额
 * @property double $investment_doing_money 在投金额
 * @property double $investment_income 投资收益
 * @property double $recommend_money 推荐佣金
 * @property double $can_withdraw_money
 * @property int $investment_number 投资个数
 * @property int $recommend_number 推荐人数
 * @property string $usdt_link
 * @property string $contract_profit
 * @property string $non_contractual_profit
 * @property string $platform_account
 * @property string $gcash_name
 * @property string $gcash_phone
 * @property string $maya_name
 * @property string $maya_phone
 */
class Account extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%member_account}}';
    }


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['merchant_id', 'member_id', 'user_integral', 'accumulate_integral', 'give_integral', 'frozen_integral', 'status', 'investment_number', 'recommend_number'], 'integer'],
            [['user_money', 'accumulate_money', 'give_money', 'consume_money', 'frozen_money', 'consume_integral', 'experience', 'investment_all_money', 'investment_doing_money', 'investment_income', 'recommend_money','can_withdraw_money','non_contractual_profit','contract_profit'], 'number'],
            [['wechat_account_url', 'alipay_account_url', 'bank_card', 'bank_address', 'wechat_account','usdt_link','platform_account','gcash_name','gcash_phone','maya_name','maya_phone'], 'string', 'max' => 255],
            [['alipay_account', 'alipay_user_name'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'merchant_id' => '商户id',
            'member_id' => '用户id',
            'user_money' => '当前余额',
            'accumulate_money' => '累计余额',
            'give_money' => '累计赠送余额',
            'consume_money' => '累计消费金额',
            'frozen_money' => '冻结金额',
            'user_integral' => '当前积分',
            'accumulate_integral' => '累计积分',
            'give_integral' => '累计赠送积分',
            'consume_integral' => '累计消费积分',
            'frozen_integral' => '冻结积分',
            'status' => '状态',
            'alipay_account_url' => '支付宝收款码',
            'wechat_account_url' => '微信收款码',
            'alipay_account' => '支付宝账号',
            'alipay_user_name' => '支付宝用户名',
            'bank_card' => '银行卡号',
            'bank_address' => '开户行',
            'wechat_account' => '微信号(open_id)',
            'experience' => '经验值',
            'investment_all_money' => '购买总额',
            'investment_doing_money' => '在购金额',
            'investment_income' => '购买收益',
            'investment_number' => '购买个数',
            'recommend_money' => '推荐佣金',
            'recommend_number' => '推荐人数',
            'usdt_link' => 'USDT地址',
            'can_withdraw_money' => '可提余额',
            'non_contractual_profit' => '非合约利润',
            'contract_profit' => '合约利润',
            'platform_account' => '平台账号',
            'gcash_name' => 'Gcash名字',
            'gcash_phone' => 'Gcash电话',
            'maya_name' => 'Maya名字',
            'maya_phone' => 'Maya电话',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMember()
    {
        return $this->hasOne(Member::class, ['id' => 'member_id']);
    }
}
