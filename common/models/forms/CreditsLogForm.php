<?php

namespace common\models\forms;

use common\models\member\Account;
use Yii;
use yii\base\Model;
use common\models\member\Member;
use common\models\member\Level;

/**
 * Class CreditsLogForm
 * @package common\models\forms
 * @author 原创脉冲
 */
class CreditsLogForm extends Model
{
    /**
     * @var Member
     */
    public $member;
    public $num = 0;
    public $credit_group;
    public $remark = '';
    public $map_id = 0;
    // 累加积累余额
    public $increase = 1;
    public $time;

    /**
     * 支付类型
     *
     * @var int
     */
    public $pay_type = 0;

    /**
     * 字段类型(请不要占用)
     *
     * @var string
     */
    public $credit_type;
}