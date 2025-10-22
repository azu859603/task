<?php

namespace backend\modules\member\forms;

use common\models\member\CreditsLog;
use Yii;
use yii\base\Model;
use yii\web\NotFoundHttpException;
use common\models\forms\CreditsLogForm;

/**
 * Class RechargeForm
 * @package backend\modules\member\forms
 * @author 原创脉冲
 */
class RechargeForm extends Model
{
    const TYPE_MONEY = 'Money'; // 余额
    const TYPE_CAN_WITHDRAW_MONEY = 'CanWithdrawMoney'; // 可提余额
    const TYPE_INT = 'Int'; // 积分
    const TYPE_EXPERIENCE = 'Experience'; // 经验

    const CHANGE_INCR = 1;
    const CHANGE_DECR = 2;

    public $old_num;
    public $change = self::CHANGE_INCR;
    public $money;
    public $int;
    public $remark;
    public $type;
    public $experience;

    protected $sercive;

    /**
     * @var array
     */
    public static $changeExplain = [
        self::CHANGE_INCR => '增加',
        self::CHANGE_DECR => '减少',
    ];

    public function rules()
    {
        return [
            [['change'], 'integer'],
            [['money', 'experience'], 'number', 'min' => 0.01, 'max' => 999999.99],
            [['int'], 'integer', 'min' => 1, 'max' => 999999],
            [['remark', 'type'], 'string'],
            [['type'], 'verifyEmpty'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'old_num' => '当前',
            'change' => '变更',
            'money' => '数量',
            'int' => '数量',
            'experience' => '数量',
            'remark' => '备注',
        ];
    }

    public function verifyEmpty()
    {
        if ($this->type == self::TYPE_MONEY && !$this->money) {
            $this->addError('money', '数量不能为空');
        }

        if ($this->type == self::TYPE_INT && !$this->int) {
            $this->addError('int', '数量不能为空');
        }
    }

    /**
     * @param $member
     * @return bool
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function save($member)
    {
        $action = 'decr' . $this->type;
        if ($this->change == self::CHANGE_INCR) {
            $action = 'incr' . $this->type;
        }

        $num = $this->money;
        if ($this->type == self::TYPE_INT) {
            $num = $this->int;
        }

        $u_id = Yii::$app->user->identity->getId();
        if ($action == "decrMoney") {
            $message = '【后台】管理员操作扣除余额';
        } elseif ($action == "incrMoney") {
            $message = '【后台】管理员操作添加余额';
        } elseif ($action == "decrInt") {
            $message = '【后台】管理员操作扣除积分';
        } elseif ($action == "incrInt") {
            $message = '【后台】管理员操作添加积分';
        } elseif ($action == "decrCanWithdrawMoney") {
            $message = '【后台】管理员操作扣除可提余额';
        } elseif ($action == "incrCanWithdrawMoney") {
            $message = '【后台】管理员操作添加可提余额';
        } else {
            $message = '【后台】管理员操作';
        }

        // 写入当前会员
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->services->member->set($member);

            if ($this->change == self::CHANGE_INCR) {
                $pay_type = CreditsLog::RECHARGE_PAY_TYPE;
            } else {
                $pay_type = CreditsLog::WITHDRAW_PAY_TYPE;
            }
            // 变动积分/余额
            Yii::$app->services->memberCreditsLog->$action(new CreditsLogForm([
                'member' => Yii::$app->services->member->get($member->id),
                'num' => $num,
                'credit_group' => CreditsLog::CREDIT_GROUP_MANAGER,
                'remark' => !empty($this->remark) ? $this->remark : $message,
                'pay_type' => $pay_type,
                'map_id' => $u_id,
            ]));

            $transaction->commit();
        } catch (NotFoundHttpException $e) {
            $transaction->rollBack();
            $this->addError('remark', $e->getMessage());
            return false;
        }

        return true;
    }
}