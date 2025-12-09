<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2019/11/21
 * Time: 4:14
 */

namespace api\modules\v1\forms\member;


use common\models\member\CreditsLog;
use yii\base\Model;
use Yii;

class CreditsLogForm extends Model
{
    public $page;
    public $pay_type;
    public $credit_type;


    // 余额或积分
    public static $credit_type_array = ['user_money', 'user_integral','can_withdraw_money','user_money_platform'];

    public function rules()
    {
        return [
            ['page', 'default', 'value' => 1],
            [['page', 'pay_type'], 'integer'],
            ['pay_type', 'in', 'range' => array_keys(CreditsLog::$PayTypeExplain)],
            ['credit_type', 'in', 'range' => self::$credit_type_array]
        ];
    }

    public function attributeLabels()
    {
        return [
            'page' => '页码',
            'pay_type' => '类型',
            'credit_type' => '查询类型',
        ];
    }
}