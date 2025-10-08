<?php

namespace api\modules\v1\forms\member;


use yii\base\Model;
use Yii;

class IntegralExchangeForm extends Model
{
    public $integral;

    // 余额或积分

    public function rules()
    {
        return [
            [['integral',], 'required'],
            [['integral',], 'number', 'min' => Yii::$app->debris->config('min_exchange_integral')],
        ];
    }

    public function attributeLabels()
    {
        return [
            'integral' => '积分',
        ];
    }
}