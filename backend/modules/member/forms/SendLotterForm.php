<?php

namespace backend\modules\member\forms;

use yii\base\Model;

class SendLotterForm extends Model
{
    public $level;
    public $number;

    public function rules()
    {
        return [
            [['level','number'], 'required'],
            [['level','number'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'level' => '会员等级',
            'number' => '赠送抽奖次数',
        ];
    }
}