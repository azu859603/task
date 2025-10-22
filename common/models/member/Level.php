<?php

namespace common\models\member;

use common\behaviors\MerchantBehavior;

/**
 * This is the model class for table "{{%member_level}}".
 *
 * @property int $id 主键
 * @property string $merchant_id 商户id
 * @property int $level 等级（数字越大等级越高）
 * @property string $name 等级名称
 * @property string $money 消费额度满足则升级
 * @property int $check_money 选中消费额度
 * @property int $integral 消费积分满足则升级
 * @property int $check_integral 选中消费积分
 * @property int $middle 条件（0或 1且）
 * @property string $discount 折扣
 * @property int $status 状态[-1:删除;0:禁用;1启用]
 * @property string $detail 会员介绍
 * @property string $created_at 创建时间
 * @property string $updated_at 修改时间
 * @property string $sign_gift_number 赠送积分
 * @property string $sign_gift_money 赠送奖金
 * @property double $experience 经验值
 * @property double $income 额外收益率
 * @property string $q_a_number 赠送积分
 * @property string $q_a_money 赠送奖金
 * @property string $upgrade_money 升级奖金
 * @property string $handling_fees_percentage
 */
class Level extends \common\models\base\BaseModel
{
    use MerchantBehavior;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%member_level}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['level'], 'unique'],
            [['merchant_id', 'level', 'check_money', 'integral', 'check_integral', 'middle', 'status', 'created_at', 'updated_at', 'sign_gift_number', 'q_a_number'], 'integer'],
            [['level', 'sign_gift_number', 'name', 'sign_gift_money'], 'required'],
            [['discount'], 'number', 'min' => 1, 'max' => 100],
            [['name', 'detail'], 'string', 'max' => 255],
            [['money', 'experience', 'income', 'sign_gift_money', 'q_a_money', 'upgrade_money','handling_fees_percentage'], 'number', 'numberPattern' => '/^\d+(.\d{1,2})?$/', 'message' => '小数点后位数不能大于俩位'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '主键',
            'merchant_id' => '商户id',
            'level' => '等级', // （数字越大等级越高）
            'name' => '等级名称',
            'money' => '消费额度满足则升级',
            'check_money' => '选中消费额度',
            'integral' => '消费积分满足则升级',
            'check_integral' => '选中消费积分',
            'middle' => '条件（0或 1且）',
            'discount' => '折扣',
            'status' => '状态',
            'detail' => '等级介绍',
            'created_at' => '创建时间',
            'updated_at' => '修改时间',
            'sign_gift_number' => '签到赠送积分',
            'sign_gift_money' => '签到赠送经验',
            'experience' => '经验值',
            'income' => '额外收益率',
            'q_a_number' => '碳问答赠送积分',
            'q_a_money' => '碳问答赠送奖金',
            'upgrade_money' => '升级奖金',
            'handling_fees_percentage' => '提现手续费',
        ];
    }
}