<?php

namespace backend\modules\member\forms;

use yii\base\Model;

class ExportForm extends Model
{
    public $level;

    public function rules()
    {
        return [
            [['level'], 'required'],
            [['level'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'level' => '导出等级',
        ];
    }
}