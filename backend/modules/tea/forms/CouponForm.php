<?php

namespace backend\modules\tea\forms;

use yii\base\Model;

class CouponForm extends Model
{
    public $content;
    public function rules()
    {
        return [
            [['content', ], 'required'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'content' => '赠送内容',
        ];
    }
}