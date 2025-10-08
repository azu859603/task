<?php

namespace common\models\tea;

use common\models\member\Member;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "t_sign_goods_bill".
 *
 * @property int $id
 * @property int $member_id 会员ID
 * @property int $g_id 商品ID
 * @property int $status 状态(0未发货,1已发货,2签收)
 * @property string $remark 备注
 * @property string $sn 订单号
 * @property string $member_remark 收货地址
 * @property string $get_username 收件人
 * @property string $get_mobile 收件人电话
 * @property int $created_at 下单时间
 * @property int $ship_time 发货时间
 * @property int $over_time 完成时间
 */
class SignGoodsBill extends \yii\db\ActiveRecord
{

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                ],
            ],
        ];
    }

    public static $statusArray = [
        1 => "未发货",
        2 => "已发货",
        3 => "已签收",
    ];

    public static $statusColorExplain = [
        1 => "danger",
        2 => "info",
        3 => "success"
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 't_sign_goods_bill';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['g_id', 'member_remark'], 'required'],
            [['member_id', 'g_id', 'status', 'created_at', 'ship_time', 'over_time'], 'integer'],
            [['remark', 'sn', 'member_remark'], 'string', 'max' => 255],
            [['get_username'], 'string', 'max' => 50],
            [['get_mobile'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'member_id' => '会员ID',
            'g_id' => '商品ID',
            'status' => '状态',
            'remark' => '快递单号',
            'created_at' => '下单时间',
            'ship_time' => '发货时间',
            'over_time' => '完成时间',
            'sn' => '订单号',
            'member_remark' => '收货地址',
            'get_username' => '收件人',
            'get_mobile' => '收件人电话',
        ];
    }

    public function getList()
    {
        return $this->hasOne(SignGoodsList::class, ['id' => 'g_id']);
    }

    public function getMember()
    {
        return $this->hasOne(Member::class, ['id' => 'member_id']);
    }
}
