<?php

namespace backend\modules\tea\forms;

use yii\base\Model;


class AddFkForm extends Model
{
    public $member_id;
    public $p1_number;
    public $p2_number;
    public $p3_number;
    public $p4_number;
    public $p5_number;

    public function rules()
    {
        return [
            [['member_id', 'p1_number', 'p2_number', 'p3_number', 'p4_number', 'p5_number'], 'required'],
            [['member_id', 'p1_number', 'p2_number', 'p3_number', 'p4_number', 'p5_number'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'member_id' => '会员账号',
            'p1_number' => '幸运虎',
            'p2_number' => '家运虎',
            'p3_number' => '开运虎',
            'p4_number' => '福运虎',
            'p5_number' => '财运虎',
        ];
    }
}