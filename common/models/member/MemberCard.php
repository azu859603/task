<?php

namespace common\models\member;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "rf_member_card".
 *
 * @property int $id
 * @property int $member_id 会员ID
 * @property string $username
 * @property string $bank_card 卡号
 * @property string $bank_address 开户行
 */
class MemberCard extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'rf_member_card';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['bank_address', 'bank_card','username'], 'required',],
            [['member_id'], 'integer'],
            [['bank_card',], 'unique', 'message' => '该银行卡卡号已存在！'],
//            [['bank_card',], 'string', 'max' => 19, 'min' => 16],
            [['bank_address','username'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'member_id' => '会员',
            'bank_card' => '卡号',
            'bank_address' => '开户行',
            'username' => '户主',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMember()
    {
        return $this->hasOne(Member::class, ['id' => 'member_id']);
    }
}
