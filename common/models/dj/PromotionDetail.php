<?php

namespace common\models\dj;

use common\models\member\Member;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "dj_promotion_detail".
 *
 * @property int $id
 * @property int $pid
 * @property int $member_id
 * @property int $clicks 点击量
 * @property int $quantity 订单量
 * @property int $created_at
 * @property string $doing_money
 * @property string $over_money
 */
class PromotionDetail extends \yii\db\ActiveRecord
{
    /**
    public function behaviors()
    {
        return [
        [
            'class' => TimestampBehavior::class,
            'attributes' => [
                ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
            [
            'class' => BlameableBehavior::class,
            'attributes' => [
                ActiveRecord::EVENT_BEFORE_INSERT => ['created_by'],
                ],
            ],
        ];
    }
    * /

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dj_promotion_detail';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pid', 'clicks', 'quantity','member_id'], 'integer'],
            [['created_at'],'safe'],
            [['over_money','doing_money'],'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pid' => 'Pid',
            'member_id' => '卖家',
            'clicks' => '点击量',
            'quantity' => '订单量',
            'created_at' => '添加时间',
            'over_money' => '已结算费用',
            'doing_money' => '未结算费用',
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
