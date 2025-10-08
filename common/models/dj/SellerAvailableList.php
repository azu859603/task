<?php

namespace common\models\dj;

use common\models\member\Member;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "dj_seller_available_list".
 *
 * @property int $id
 * @property int $member_id
 * @property int $pid
 * @property int $created_at 上架时间
 * @property int $status 状态
 */
class SellerAvailableList extends \yii\db\ActiveRecord
{

    public function behaviors()
    {
        return [
        [
            'class' => TimestampBehavior::class,
            'attributes' => [
                ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                ActiveRecord::EVENT_BEFORE_UPDATE => ['created_at'],
                ],
            ],
        ];
    }

    public static $statusExplain = [0 => '已下架', 1 => "已上架"];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dj_seller_available_list';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['member_id', 'pid'], 'required'],
            [['member_id', 'pid', 'created_at', 'status'], 'integer'],
        ];
    }

    public static $statusColorExplain = [
        0 => "danger",
        1 => "success",
    ];

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'member_id' => 'Member ID',
            'pid' => 'PID',
            'created_at' => '更新时间',
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

    public function getShortPlaysList()
    {
        return $this->hasOne(ShortPlaysList::class, ['id' => 'pid']);
    }
}
