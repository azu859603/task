<?php

namespace common\models\tea;

use common\models\backend\Member;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "t_activity_money_setting".
 *
 * @property int $id
 * @property string $money 中奖金额
 * @property string $proportion 中奖概率
 * @property int $pid 所属活动ID
 * @property int $status 状态
 * @property int $created_at 创建时间
 * @property int $created_by 创建人
 */
class ActivityMoneySetting extends \yii\db\ActiveRecord
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
        return 't_activity_money_setting';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['money', 'proportion'], 'number'],
//            [['pid'], 'required'],
            [['pid', 'status', 'created_at', 'created_by'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'money' => '中奖金额',
            'proportion' => '中奖概率',
            'pid' => '所属活动ID',
            'status' => '状态',
            'created_at' => '创建时间',
            'created_by' => '创建人',
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
}
