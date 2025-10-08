<?php

namespace common\models\member;

use common\enums\AppEnum;
use common\enums\StatusEnum;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "base_realname_audit".
 *
 * @property int $id
 * @property int $member_id 会员ID
 * @property string $realname 真实姓名
 * @property string $identification_number 证件号码
 * @property string $front 证件正面
 * @property string $reverse 证件反面
 * @property string $remark
 * @property int $status 状态
 * @property int $created_by
 * @property int $type
 * @property int $created_at
 */
class RealnameAudit extends \yii\db\ActiveRecord
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
        0 => '未审核',
        1 => '通过',
        2 => '拒绝',
    ];

    public static $typeExplain = [
        1 => '身份证',
        2 => '驾驶证',
        3 => '护照',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'base_realname_audit';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['realname', 'identification_number','type'], 'required'],
            [['member_id', 'status','created_by','type','created_at'], 'integer'],
            [['realname', 'identification_number'], 'string', 'max' => 50],
            [['front', 'reverse','remark'], 'string', 'max' => 255],
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
            'realname' => '真实姓名',
            'identification_number' => '证件号码',
            'front' => '证件正面',
            'reverse' => '证件反面',
            'status' => '状态',
            'remark' => '备注',
            'created_by' => '审核人',
            'type' => '类型',
            'created_at' => '添加时间',
        ];
    }

    /**
     * 关联用户
     * @return ActiveQuery
     */
    public function getMember()
    {
        return $this->hasOne(Member::class, ['id' => 'member_id']);
    }

    /**
     * 关联管理员
     * @return \yii\db\ActiveQuery
     * @author 原创脉冲
     */
    public function getManager()
    {
        return $this->hasOne(\common\models\backend\Member::class, ['id' => 'created_by']);
    }
}
