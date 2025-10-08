<?php

namespace backend\modules\member\forms;

use yii\base\Model;

class BuyerForm extends Model
{
    public $password;
    public $user_money;
    public $pid;
    public $number;

    public function rules()
    {
        return [
            [['password', 'pid', 'number'], 'required'],
            [['number'], 'integer', 'min' => 1, 'max' => 20],
            [['password'], 'string', 'max' => 15],
            [['user_money'], 'number', 'numberPattern' => '/^\d+(.\d{1,2})?$/', 'message' => '小数点后位数不能大于俩位','max' => 999999.99],
        ];
    }

    public function attributeLabels()
    {
        return [
            'password' => '密码',
            'user_money' => '余额',
            'pid' => '上级',
            'number' => '创建数量',
        ];
    }
}