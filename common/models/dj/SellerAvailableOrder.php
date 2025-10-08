<?php

namespace common\models\dj;

use common\models\member\Member;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "dj_seller_available_order".
 *
 * @property int $id
 * @property int $member_id
 * @property int $pid
 * @property int $number
 * @property int $buy_number
 * @property int $created_at
 * @property string $money
 * @property string $buy_money
 */
class SellerAvailableOrder extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dj_seller_available_order';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['member_id', 'pid', 'number', 'buy_number', 'created_at'], 'integer'],
            [['money', 'buy_money'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'member_id' => '卖家',
            'pid' => '上架短剧',
            'number' => '预售数量',
            'buy_number' => '剩余数量',
            'created_at' => '下单时间',
            'money' => '预售金额',
            'buy_money' => '剩余金额',
        ];
    }

    public function getSellerAvailableList()
    {
        return $this->hasOne(SellerAvailableList::class, ['pid' => 'id']);
    }

    /**
     * 关联用户表
     * @return \yii\db\ActiveQuery
     * @author 哈哈
     */
    public function getMember()
    {
        return $this->hasOne(Member::class, ['id' => 'member_id']);
    }
}
