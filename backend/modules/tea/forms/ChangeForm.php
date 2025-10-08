<?php

namespace backend\modules\tea\forms;

use yii\base\Model;

class ChangeForm extends Model
{
    public $member_id;
    public $b_id;
    public $type;
    public function rules()
    {
        return [
            [['member_id', 'b_id','type'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'member_id' => '会员账号',
            'b_id' => '管理账号',
            'type' => '类型',
        ];
    }
}