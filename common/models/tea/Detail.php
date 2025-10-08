<?php

namespace common\models\tea;

use common\models\member\Member;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "dk_detail".
 *
 * @property int $id
 * @property int $member_id 会员
 * @property int $b_id 订单
 * @property int $project_id 订单
 * @property string $content 凭证
 * @property int $status 状态
 * @property int $created_at
 */
class Detail extends \yii\db\ActiveRecord
{
    /**
    public function behaviors()
    {
        return [
        [
            'class' => TimestampBehavior::class,
            'attributes' => [
                ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
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
    * /

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dk_detail';
    }

    public static $statusArray = [
        0 => "待审核",
        1 => "已通过",
        2 => "已拒绝",
    ];

    /**
     * 颜色
     * @var array
     * @author 哈哈
     */
    public static $statusColor = [
        0 => "primary",
        1 => "success",
        2 => "danger",
    ];


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['member_id', 'b_id', 'content'], 'required'],
            [['member_id', 'b_id', 'status','created_at','project_id'], 'integer'],
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
            'b_id' => '订单ID',
            'content' => '凭证',
            'status' => '状态',
            'created_at' => '打卡时间',
            'project_id' => '项目ID',
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

    public function getBill(){
        return $this->hasOne(Bill::class,['id'=>'b_id']);
    }
}
