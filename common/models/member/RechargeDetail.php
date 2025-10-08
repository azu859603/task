<?php

namespace common\models\member;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "base_recharge_detail".
 *
 * @property int $id
 * @property string $title 通道名称
 * @property int $pid 平台ID
 * @property int $type 类型
 * @property string $code 通道值
 * @property string $min_money 最低充值金额
 * @property string $max_money 最高充值金额
 * @property int $sort 排序
 * @property int $status 状态
 * @property string $remark 备注
 * @property string $payee
 * @property string $bank_name
 * @property string $bank_card
 * @property string $help
 * @property string $usdt_trc20
 * @property string $exchange_rate
 * @property string $zfb_name
 * @property string $zfb_number
 */
class RechargeDetail extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'base_recharge_detail';
    }

    public static $typeExplain = [
        1 => "微信",
        2 => "支付宝",
        3 => "网银",
    ];


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'pid', 'min_money', 'max_money'], 'required'],
            [['type', 'sort', 'status','pid'], 'integer'],
            [['min_money', 'max_money'], 'number'],
            [['title'], 'string', 'max' => 100],
            [['code'], 'string', 'max' => 50],
            [['remark','payee','bank_name','bank_card','usdt_trc20','zfb_name','zfb_number'], 'string', 'max' => 255],
            [['min_money', 'max_money','exchange_rate'], 'number', 'numberPattern' => '/^\d+(.\d{1,2})?$/', 'message' => '小数点后位数不能大于俩位'],
            [['help'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => '通道名称',
            'pid' => '充值平台',
            'type' => '类型',
            'code' => '通道值',
            'min_money' => '最低充值金额',
            'max_money' => '最高充值金额',
            'sort' => '排序',
            'status' => '状态',
            'remark' => '备注',
            'payee' => '收款人',
            'bank_name' => '开户行',
            'bank_card' => '银行卡号',
            'help' => '帮助说明',
            'usdt_trc20' => 'USDT-TRC20',
            'exchange_rate' => '汇率',
            'zfb_number' => '支付宝账号',
            'zfb_name' => '支付宝户主',
        ];
    }

    /**
     * 关联平台
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(RechargeCategory::class, ['id' => 'pid']);
    }
}
