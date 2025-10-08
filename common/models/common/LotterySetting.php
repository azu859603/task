<?php

namespace common\models\common;

use common\enums\StatusEnum;
use common\models\backend\Member;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "base_lottery_setting".
 *
 * @property int $id
 * @property string $banner 奖项图片
 * @property string $lottery_name 奖项名称
 * @property string $title 奖品名称
 * @property string $lottery_amount 中奖金额
 * @property string $proportion 中奖百分比
 * @property int $sort 排序(越大越靠前)
 * @property int $status 状态 0禁用 1正常
 * @property int $type 奖品类型(1,奖金,2奖品)
 * @property int $created_at 添加时间
 * @property int $created_by 添加人
 */
class LotterySetting extends \yii\db\ActiveRecord
{


    public static $typeExplain = [
        1 => "奖金",
        2 => "奖品",
    ];

    public static $typeColorExplain = [
        1 => "info",
        2 => "success",
    ];

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
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_by'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'base_lottery_setting';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['lottery_name', 'lottery_amount', 'proportion', 'title'], 'required'],
            [['sort', 'status', 'type', 'created_at', 'created_by'], 'integer'],
            [['banner', 'title'], 'string', 'max' => 255],
            [['lottery_name'], 'string', 'max' => 50],
            [['lottery_amount', 'proportion'], 'number', 'numberPattern' => '/^\d+(.\d{1,2})?$/', 'message' => '小数点后位数不能大于俩位'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'banner' => '奖项图片(270X270)',
            'lottery_name' => '奖项名称',
            'lottery_amount' => '中奖金额',
            'proportion' => '中奖百分比',
            'sort' => '排序',
            'status' => '状态',
            'type' => '奖品类型',
            'created_at' => '添加时间',
            'created_by' => '添加人',
            'title' => '奖品名称',
        ];
    }

    /**
     * 关联管理员
     * @return \yii\db\ActiveQuery
     * @author 原创脉冲
     */
    public function getManager()
    {
        return $this->hasOne(Member::class, ['id' => 'created_by']);
    }

    /**
     * 获取VIP抽奖所有数据
     * @return array|\yii\db\ActiveRecord[]
     * @author 哈哈
     */
    public static function getAllModel()
    {
        return self::find()
            ->where(['status' => StatusEnum::ENABLED])
            ->select(['id', 'lottery_name', 'lottery_amount', 'proportion', 'banner', 'type'])
            ->orderBy(['sort' => SORT_ASC, 'created_at' => SORT_DESC])
            ->all();
    }
}
