<?php

namespace backend\modules\member\forms;

use yii\base\Model;

class ExportMoneyAllForm extends Model
{
    public $start_money;
    public $stop_money;

    public function rules()
    {
        return [
            [['start_money','stop_money'], 'required'],
            [['start_money','stop_money'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'start_money' => '投资开始金额',
            'stop_money' => '投资结束金额',
        ];
    }
}