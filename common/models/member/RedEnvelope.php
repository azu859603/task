<?php

namespace common\models\member;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "t_red_envelope".
 *
 * @property int $id
 * @property int $member_id 会员
 * @property string $title 红包名称
 * @property string $money 金额
 * @property int $is_get 是否领取
 * @property int $created_at 添加时间
 * @property int $updated_at 领取时间
 * @property int $type 类型
 */
class RedEnvelope extends \yii\db\ActiveRecord
{
    public static $isGetExplain = [0 => "未领取", 1 => "已领取"];

    public $file;
    public $level;

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 't_red_envelope';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['member_id'], 'required', 'on' => 'noAll'],
            [['title', 'money'], 'required'],
            [['is_get', 'created_at', 'updated_at', 'type'], 'integer'],
            [['money'], 'number', 'numberPattern' => '/^\d+(.\d{1,2})?$/', 'message' => '小数点后位数不能大于俩位'],
            [['title'], 'string', 'max' => 255],
            [['member_id', 'level'], 'safe'],
            [['file'], 'file', 'extensions' => 'txt', 'maxSize' => 1024 * 1024 * 10, 'maxFiles' => 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'member_id' => '会员',
            'title' => '红包名称',
            'money' => '金额',
            'is_get' => '是否领取',
            'created_at' => '添加时间',
            'updated_at' => '领取时间',
            'file' => '会员文件',
            'level' => '会员等级',
            'type' => '类型',
        ];
    }

    /**
     * 关联用户
     * @return \yii\db\ActiveQuery
     */
    public function getMember()
    {
        return $this->hasOne(Member::class, ['id' => 'member_id']);
    }
}
