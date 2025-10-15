<?php

namespace common\models\task;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "task_project".
 *
 * @property int $id
 * @property string $banner 图标
 * @property int $all_number 任务数量
 * @property int $remain_number 剩余数量
 * @property int $vip_level 等级要求
 * @property string $money 任务佣金
 * @property int $experience
 * @property int $code_switch 活动码开关
 * @property array $images_list 图片素材
 * @property array $file_list 文件素材
 * @property array $keywords 关键词
 * @property int $sort
 * @property int $status
 * @property int $pid
 * @property int $created_at
 * @property int $limit_number
 * @property int $is_top
 */
class Project extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task_project';
    }


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


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['all_number', 'remain_number'], 'required'],
            [['all_number', 'remain_number', 'vip_level', 'code_switch', 'sort', 'status', 'experience', 'pid', 'created_at','limit_number','is_top'], 'integer'],
            [['money'], 'number'],
            [['images_list', 'file_list'], 'safe'],
            [['banner', 'keywords'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'banner' => '图标',
            'all_number' => '任务数量',
            'remain_number' => '剩余数量',
            'vip_level' => '等级要求',
            'money' => '任务佣金',
            'code_switch' => '活动码',
            'images_list' => '图片素材',
            'file_list' => '文件素材',
            'keywords' => '关键词',
            'status' => '状态',
            'sort' => '排序',
            'experience' => '经验值',
            'pid' => '分类',
            'created_at' => '分类',
            'limit_number' => '限制次数',
            'is_top' => '是否推荐',
        ];
    }

    public function getTranslation()
    {
        return $this->hasOne(ProjectTranslations::class, ['pid' => 'id']);
    }

    public function getTranslations()
    {
        return $this->hasMany(ProjectTranslations::class, ['pid' => 'id']);
    }
}
