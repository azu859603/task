<?php

namespace common\models\task;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "task_ai_content".
 *
 * @property int $id
 * @property int $pid 任务ID
 * @property int $oid 订单ID
 * @property int $type 类型
 * @property string $ai_content AI文案
 * @property string $content 内容
 * @property int $status 状态
 */
class AiContent extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task_ai_content';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pid', 'ai_content'], 'required'],
            [['pid', 'type', 'status','oid'], 'integer'],
            [['ai_content', 'content'], 'string'],
        ];
    }

    public static $statusExplain = [0 => '未使用', 1 => "使用中", 2 => "已使用"];

    public static $typeExplain = [1 => '图片素材', 2 => "文案素材"];

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pid' => '任务ID',
            'oid' => '订单ID',
            'type' => '类型',
            'ai_content' => 'AI文案',
            'content' => '内容',
            'status' => '状态',
        ];
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
