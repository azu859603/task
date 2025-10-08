<?php

namespace common\models\tea;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "dk_notify".
 *
 * @property int $id
 * @property int $member_id
 * @property string $content
 * @property int $created_at
 * @property int $type 类型，1系统通知，2打卡通知
 */
class Notify extends \yii\db\ActiveRecord
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
        ];
    }


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dk_notify';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['member_id', 'created_at', 'type'], 'integer'],
            [['content'], 'string', 'max' => 255],
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
            'content' => '内容',
            'created_at' => '时间',
            'type' => '类型，1系统通知，2打卡通知',
        ];
    }
}
