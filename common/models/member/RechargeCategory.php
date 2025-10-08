<?php

namespace common\models\member;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "base_recharge_category".
 *
 * @property int $id
 * @property string $title 支付名称
 * @property string $pay_url 支付网关
 * @property string $key 支付密钥
 * @property string $account 支付账户
 * @property string $notify_url 回调地址
 * @property string $other
 * @property int $type
 * @property int $status
 * @property string $withdraw_url
 * @property string $withdraw_account
 * @property string $withdraw_key
 * @property string $withdraw_notify_url
 * @property string $withdraw_other
 */
class RechargeCategory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'base_recharge_category';
    }

    public static $typeExplain = [
        1 => "地址",
        2 => "文本",
    ];

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'pay_url','type'], 'required'],
            [['type','status'], 'integer'],
            [['title'], 'string', 'max' => 100],
            [['pay_url', 'key', 'account', 'notify_url','withdraw_url','withdraw_account','withdraw_key'], 'string', 'max' => 255],
            [['notify_url','withdraw_notify_url'], 'string', 'max' => 50],
            [['other','withdraw_other'],'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => '平台名称',
            'pay_url' => '代收网关',
            'account' => '代收账户',
            'key' => '代收密钥',
            'notify_url' => '代收回调地址',
            'type' => '类型',
            'other' => '代收其他',
            'withdraw_url' => '代付网关',
            'withdraw_account' => '代付账户',
            'withdraw_key' => '代付密钥',
            'withdraw_notify_url' => '代付回调地址',
            'withdraw_other' => '代付其他',
            'status' => '状态',
        ];
    }
}
