<?php

namespace common\models\dj;

use common\models\member\Member;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "dj_orders".
 *
 * @property int $id
 * @property int $member_id
 * @property int $seller_id
 * @property int $pid
 * @property string $money 售价
 * @property string $dx_money 代销价
 * @property string $income 利润
 * @property string $private_key 密钥
 * @property int $key_status 密钥状态
 * @property int $status 状态
 * @property int $income_status 状态
 * @property int $created_at 购买时间
 * @property int $updated_at 发货时间
 * @property int $over_time
 * @property int $push_flow_switch
 */
class Orders extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dj_orders';
    }


    public static $statusExplain = [0 => '待发货', 1 => "已发货", 2 => "已退货"];

    public static $keyStatusExplain = [0 => '未解锁', 1 => "已解锁"];

    public static $incomeStatusExplain = [0 => '未收益', 1 => "已收益"];

    public static $statusColorExplain = [
        0 => "danger",
        1 => "success",
    ];

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['member_id', 'seller_id', 'pid'], 'required'],
            [['member_id', 'seller_id', 'pid', 'key_status', 'status', 'created_at', 'updated_at', 'income_status', 'over_time','push_flow_switch'], 'integer'],
            [['money', 'income','dx_money'], 'number'],
            [['private_key'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'member_id' => '买家',
            'seller_id' => '卖家',
            'pid' => '剧集',
            'money' => '售价',
            'income' => '利润',
            'dx_money' => '代销价',
            'private_key' => '密钥',
            'key_status' => '密钥状态',
            'status' => '订单状态',
            'created_at' => '购买时间',
            'updated_at' => '发货时间',
            'over_time' => '收益时间',
            'income_status' => '收益状态',
            'push_flow_switch' => '推流开关',
        ];
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

    /**
     * 关联用户表
     * @return \yii\db\ActiveQuery
     * @author 哈哈
     */
    public function getSeller()
    {
        return $this->hasOne(Member::class, ['id' => 'seller_id']);
    }

//    public function getSellerAvailable()
//    {
//        return $this->hasOne(SellerAvailableList::class, ['id' => 'pid']);
//    }

    public function getShortPlaysList()
    {
        return $this->hasOne(ShortPlaysList::class, ['id' => 'pid']);
    }
}
