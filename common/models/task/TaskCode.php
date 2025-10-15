<?php

namespace common\models\task;

use common\models\member\Member;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "task_code".
 *
 * @property int $id
 * @property int $member_id
 * @property int $t_id
 * @property int $status
 * @property string $code
 */
class TaskCode extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task_code';
    }

    public static $statusExplain = [0 => '未使用', 1 => "已使用"];


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['member_id', 't_id', 'status'], 'integer'],
            [['code'], 'string', 'max' => 50],
            [['code'], 'unique',]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            't_id' => '订单ID',
            'member_id' => '会员',
            'code' => '活动码',
            'status' => '状态',
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
}
