<?php

namespace backend\modules\dj\forms;

use yii\base\Model;

class AddOrderForm extends Model
{
    public $seller_id;
    public $member_id;
    public $start_time;
    public $stop_time;

    public function rules()
    {
        return [
            [['seller_id'], 'required', 'on' => ['add_one']],
            [['member_id', 'start_time','stop_time'], 'required', 'on' => ['add_two']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'seller_id' => '卖家账号',
            'member_id' => '买家数量',
            'start_time' => '下单开始时间',
            'stop_time' => '下单停止时间',
        ];
    }
}