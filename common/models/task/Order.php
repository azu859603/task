<?php

namespace common\models\task;

use common\models\member\Member;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "task_order".
 *
 * @property int $id
 * @property int $member_id 会员
 * @property int $pid 任务
 * @property int $status 状态
 * @property int $created_at 添加时间
 * @property int $updated_at 完成时间
 * @property string $video_url 视频地址
 * @property array $images_list 任务截图
 * @property string $money 任务佣金
 * @property string $code 活动码
 * @property int $push_number
 */
class Order extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task_order';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['member_id', 'pid', 'created_at'], 'required'],
            [['member_id', 'pid', 'status', 'created_at', 'updated_at','push_number'], 'integer'],
            [['images_list'], 'safe'],
            [['money'], 'number'],
            [['video_url', 'code'], 'string', 'max' => 255],
        ];
    }

    public static $statusExplain = [0 => '待提交', 1 => "已提交", 2 => "已通过", 3 => "已拒绝"];


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'member_id' => '会员',
            'pid' => '任务',
            'status' => '状态',
            'created_at' => '添加时间',
            'updated_at' => '完成时间',
            'video_url' => '视频地址',
            'images_list' => '任务截图',
            'money' => '任务佣金',
            'code' => '活动码',
            'push_number' => '已提交次数',
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
     * 关联用户表
     * @return \yii\db\ActiveQuery
     * @author 哈哈
     */
    public function getProject()
    {
        return $this->hasOne(Project::class, ['id' => 'pid']);
    }
}
