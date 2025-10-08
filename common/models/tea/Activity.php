<?php

namespace common\models\tea;

use common\models\backend\Member;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "t_activity".
 *
 * @property int $id
 * @property string $title 活动名称
 * @property string $banner 活动图片
 * @property int $open_time 开奖时间
 * @property int $stop_time 开奖时间
 * @property int $created_at 创建时间
 * @property int $created_by 创建人
 * @property int $status 状态
 */
class Activity extends \yii\db\ActiveRecord
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

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 't_activity';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['created_at', 'created_by', 'status'], 'integer'],
            [['title', 'banner'], 'string', 'max' => 255],
            [['open_time'], 'datetime', 'timestampAttribute' => 'open_time'],
            [['stop_time'], 'datetime', 'timestampAttribute' => 'stop_time'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => '活动名称',
            'banner' => '活动图片',
            'open_time' => '开奖开始时间',
            'stop_time' => '开奖停止时间',
            'created_at' => '创建时间',
            'created_by' => '创建人',
            'status' => '状态',
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
}
