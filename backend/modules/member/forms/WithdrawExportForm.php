<?php

namespace backend\modules\member\forms;

use yii\base\Model;

class WithdrawExportForm extends Model
{
    public $created_at;

    public function rules()
    {
        return [
            [['created_at'], 'required'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'created_at' => '日期',
        ];
    }
}