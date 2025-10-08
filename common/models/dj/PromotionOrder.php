<?php

namespace common\models\dj;

use common\enums\AppEnum;
use common\models\member\Member;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "dj_promotion_order".
 *
 * @property int $id
 * @property string $title
 * @property int $member_id
 * @property int $type
 * @property int $status
 * @property string $money
 * @property int $all_number
 * @property int $number
 */
class PromotionOrder extends \yii\db\ActiveRecord
{

    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['member_id'],
                ],
                'value' => function () {
                    if (Yii::$app->id == AppEnum::API) {
                        return Yii::$app->user->identity->member_id;
                    } else {
                        return 0;
                    }
                },
            ],
        ];
    }

    public static $statusExplain = [
        0 => "关闭",
        1 => "开启",
    ];

    public static $statusColorExplain = [
        0 => "danger",
        1 => "success",
    ];

    public static $typeExplain = [
        1 => "订单",
    ];


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dj_promotion_order';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'money','type'], 'required'],
            [['member_id', 'type', 'status','all_number','number'], 'integer'],
            [['money'], 'number'],
            [['title'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => '名称',
            'member_id' => '卖家',
            'type' => '类型',
            'status' => '状态',
            'money' => '费用',
            'all_number' => '总量',
            'number' => '剩余量',
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
