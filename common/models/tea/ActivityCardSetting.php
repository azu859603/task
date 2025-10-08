<?php

namespace common\models\tea;

use common\models\backend\Member;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "t_activity_card_setting".
 *
 * @property int $id
 * @property int $pid 所属活动ID
 * @property string $title 福卡名称
 * @property string $proportion 抽中概率
 * @property string $banner1 福卡有卡图
 * @property string $banner2 福卡无卡图
 * @property int $status 状态
 * @property int $created_at 创建时间
 * @property int $created_by 创建人
 * @property int $type 类型
 */
class ActivityCardSetting extends \yii\db\ActiveRecord
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
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_by'],
                ],
            ],
        ];
    }

    public static $typeExplain = [
        1 => "收集卡",
        2 => "合成卡",
    ];


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 't_activity_card_setting';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
//            [['pid'], 'required'],
            [['pid', 'status', 'created_at', 'created_by', 'type'], 'integer'],
            [['proportion'], 'number'],
            [['title'], 'string', 'max' => 10],
            [['banner1', 'banner2'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pid' => '所属活动ID',
            'title' => '福卡名称',
            'proportion' => '抽中概率',
            'banner1' => '福卡有卡图',
            'banner2' => '福卡无卡图',
            'status' => '状态',
            'created_at' => '创建时间',
            'created_by' => '创建人',
            'type' => '类型',
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
     * 关联上级
     * @return \yii\db\ActiveQuery
     * @author 原创脉冲
     */
    public function getActivity()
    {
        return $this->hasOne(Activity::class, ['id' => 'pid']);
    }

    public function getCardList()
    {
        return $this->hasOne(ActivityCardList::class, ['pid' => 'id']);
    }
}
