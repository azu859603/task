<?php

namespace backend\modules\tea\forms;

use yii\base\Model;

class ZhuantouForm extends Model
{

    public $investment_amount;
    public $pid;
    public $member_id;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['investment_amount', 'pid', 'member_id'], 'required'],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'investment_amount' => '转购金额',
            'pid' => '转购产品',
            'member_id' => '被转购人',
        ];
    }

}