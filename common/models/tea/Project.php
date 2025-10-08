<?php

namespace common\models\tea;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "dk_project".
 *
 * @property int $id
 * @property string $label 标签
 * @property string $title 标题
 * @property string $rule
 * @property string $least_amount 起投金额
 * @property string $income 收益
 * @property int $deadline 项目期限
 * @property int $sort 排序
 * @property int $status 状态
 */
class Project extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dk_project';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['label', 'title'], 'required'],
            [['least_amount', 'income'], 'number'],
            [['deadline', 'sort', 'status'], 'integer'],
            [['label', 'title', 'rule'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'label' => '标签',
            'title' => '标题',
            'least_amount' => '起投金额',
            'income' => '日化收益',
            'deadline' => '项目期限',
            'sort' => '排序',
            'status' => '状态',
            'rule' => '规则',
        ];
    }

    /**
     * 关联订单
     * @return \yii\db\ActiveQuery
     */
    public function getBill()
    {
        return $this->hasOne(Bill::class, ['project_id' => 'id']);
    }
}
