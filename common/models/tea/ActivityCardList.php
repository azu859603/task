<?php

namespace common\models\tea;

use common\models\member\Member;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "t_activity_card_list".
 *
 * @property int $id
 * @property int $pid 所属福卡ID
 * @property int $member_id 会员ID
 * @property string remark 备注
 * @property int $type 类型 1投资获得 2下级投资获得 3赠送获得
 * @property int $status 状态 0未使用 1已使用
 * @property int $created_at 获得时间
 * @property int $updated_at 使用时间
 */
class ActivityCardList extends \yii\db\ActiveRecord
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


    public static $typeExplain = [
        1 => '我的购买',
        2 => '下级购买',
        3 => '好友赠送',
        4 => '五虎合一'
    ];

    public static $statusExplain = [
        0 => '未使用',
        1 => '已使用'
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 't_activity_card_list';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pid', 'member_id', 'type'], 'required'],
            [['pid', 'member_id', 'type', 'status', 'created_at', 'updated_at'], 'integer'],
            [['remark'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pid' => '所属福卡ID',
            'member_id' => '会员ID',
            'remark' => '备注',
            'type' => '类型',
            'status' => '状态',
            'created_at' => '获得时间',
            'updated_at' => '使用时间',
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
     * 关联卡片
     * @return \yii\db\ActiveQuery
     */
    public function getCard()
    {
        return $this->hasOne(ActivityCardSetting::class, ['id' => 'pid']);
    }
}
