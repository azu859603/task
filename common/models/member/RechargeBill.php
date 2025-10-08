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
use yii\web\UnprocessableEntityHttpException;

/**
 * This is the model class for table "base_recharge_bill".
 *
 * @property int $id
 * @property int $member_id 会员ID
 * @property string $sn 订单号
 * @property string $recharge_money 充值金额
 * @property string $real_recharge_money 充值金额
 * @property string $username 充值人姓名
 * @property int $type 充值类型
 * @property int $created_at 充值时间
 * @property int $updated_at 审核时间
 * @property int $status 状态(0未审核,1通过,2拒绝,3未支付)
 * @property string $remark 备注
 * @property string $user_remark 附言
 * @property int $warning_switch 提醒开关
 * @property string $pay_code
 * @property string $images
 */
class RechargeBill extends \yii\db\ActiveRecord
{

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

    public static $statusExplain = [
        0 => "待审核",
        1 => "已通过",
        2 => "已拒绝",
        3 => "未支付"
    ];

//    public static $typeExplain = [
//        1 => "微信WAP",
//        2 => "支付宝WAP",
//        10 => "支付宝扫码",
//        11 => "转账银行卡1",
//        12 => "微信扫码",
//        13 => "转账银行卡2",
//        14 => "对公转账",
//        15 => "支付宝转账",
//        16 => "支付宝在线转账",
//        17 => "转账银行卡3",
//    ];

    public static $statusColorExplain = [
        0 => "info",
        1 => "success",
        2 => "warning",
        3 => "danger"
    ];


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'base_recharge_bill';
    }

    /**
     * 生成新的充值订单
     * @param $sn
     * @param $recharge_money
     * @param $username
     * @param $type
     * @param $status
     * @param $user_remark
     * @param $pay_code
     * @return bool
     * @throws UnprocessableEntityHttpException
     */
    public static function createdModel($sn, $recharge_money, $real_recharge_money,$username, $type, $status, $user_remark = "", $pay_code = "",$images = "")
    {
        $model = new self();
        $model->sn = $sn;
        $model->recharge_money = $recharge_money;
        $model->real_recharge_money = $real_recharge_money;
        $model->username = $username;
        $model->type = $type;
        $model->status = $status;
        $model->user_remark = $user_remark;
        $model->pay_code = $pay_code;
        $model->images = $images;
        if ($model->save()) {
            return true;
        } else {
            $error = array_values($model->errors) ? array_values($model->errors) : [['系统繁忙,请稍后再试!']];
            throw new UnprocessableEntityHttpException($error[0][0]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sn', 'recharge_money', 'type'], 'required'],
            [['member_id', 'type', 'created_at', 'updated_at', 'status', 'warning_switch'], 'integer'],
            [['sn','pay_code'], 'string', 'max' => 50],
            [['username'], 'string', 'max' => 100],
            [['remark', 'user_remark','images'], 'string', 'max' => 255],
            [['sn'], 'unique'],
            [['recharge_money','real_recharge_money'], 'number', 'numberPattern' => '/^\d+(.\d{1,2})?$/', 'message' => '小数点后位数不能大于俩位'],
        ];
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
            'recharge_money' => '充值金额',
            'real_recharge_money' => '汇款金额',
            'username' => '充值人姓名',
            'type' => '充值类型',
            'created_at' => '充值时间',
            'updated_at' => '审核时间',
            'status' => '状态',
            'remark' => '备注',
            'user_remark' => '附言',
            'warning_switch' => '提醒开关',
            'pay_code' => '通道值',
            'images' => '凭证',
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
     * @throws \yii\base\Exception
     */
    public function beforeSave($insert)
    {
        // 新增
        if ($this->isNewRecord) {
            $this->member_id = Yii::$app->user->identity['member_id'];
        } else { //修改
            // 如果是通过 则增加账户余额
            if ($this->isAttributeChanged('status') && $this->status == 1 && $this->recharge_money > 0) {
                // 充值成功 用户本金增加
                $member = Member::findOne($this->member_id);
                $member->principal = BcHelper::add( $member->principal,$this->recharge_money);
                $member->recharge_money = BcHelper::add($member->recharge_money, $this->recharge_money);
                $member->save(false);
                Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                    'member' => Member::findOne($this->member_id),
                    'num' => $this->recharge_money,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => '【系统】余额充值',
                    'pay_type' => CreditsLog::RECHARGE_PAY_TYPE
                ]));
                // 赠送 如果是银行卡入账
                $gift_percentage = Yii::$app->services->memberRechargeConfig->getGiveMoney($this->recharge_money);
                if ($gift_percentage > 0) {
                    // 计算赠送金额
                    $gift_money = BcHelper::mul($this->recharge_money, BcHelper::div($gift_percentage, 100, 4));
                    Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                        'member' => Member::findOne($this->member_id),
                        'pay_type' => CreditsLog::RECHARGE_PAY_TYPE,
                        'num' => $gift_money,
                        'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                        'remark' => "【系统】充值赠送",
                    ]));
                }
                // 加入统计表
                if ($member['type'] == 1) {
                    // 加入统计表 获取最上级用户ID
                    $first_member = Member::getParentsFirst($member);
                    $b_id = $first_member['b_id'] ?? 0;
                    Statistics::updateRechargeMoney(date("Y-m-d"), $this->recharge_money,$this->member_id,$b_id);
                }
            }
        }
        return parent::beforeSave($insert);
    }
}
