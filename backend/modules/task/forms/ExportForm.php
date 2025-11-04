<?php

namespace backend\modules\task\forms;

use yii\base\Model;

class ExportForm extends Model
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