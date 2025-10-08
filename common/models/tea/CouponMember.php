<?php

namespace common\models\tea;

use common\models\member\Member;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "t_coupon_member".
 *
 * @property int $id
 * @property int $c_id 优惠卷ID
 * @property int $type 类型
 * @property int $start_time 有效期开始时间
 * @property int $stop_time 有效期结束时间
 * @property int $status 状态（0，未使用，1已使用，2已禁用）
 * @property int $member_id
 */
class CouponMember extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 't_coupon_member';
    }

    public static $statusExplain = [
        0 => "未使用",
        1 => "已使用",
        2 => "已禁用",
    ];

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['c_id', 'type', 'start_time', 'stop_time', 'member_id'], 'required'],
            [['c_id', 'type', 'start_time', 'stop_time', 'status', 'member_id'], 'integer'],
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
            'c_id' => '优惠卷',
            'type' => '类型',
            'start_time' => '有效期开始时间',
            'stop_time' => '有效期结束时间',
            'status' => '状态',
        ];
    }

    /**
     * 关联用户
     * @return \yii\db\ActiveQuery
     */
    public function getMember()
    {
        return $this->hasOne(Member::class, ['id' => 'member_id']);
    }

    /**
     * 关联优惠卷
     * @return \yii\db\ActiveQuery
     */
    public function getCoupon()
    {
        return $this->hasOne(CouponList::class, ['id' => 'c_id']);
    }

    /**
     * 添加
     * @param $member_id
     * @param $c_id
     * @param $type
     * @param $start_time
     * @param $stop_time
     * @return bool
     */
    public static function createModel($member_id, $c_id, $type, $start_time, $stop_time)
    {
        $model = new self();
        $model->member_id = $member_id;
        $model->c_id = $c_id;
        $model->type = $type;
        $model->start_time = $start_time;
        $model->stop_time = $stop_time;
        if (!$model->save()) {
            return false;
        }
        return true;
    }
}
