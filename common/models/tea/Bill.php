<?php

namespace common\models\tea;

use common\models\member\Member;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "dk_bill".
 *
 * @property int $id
 * @property int $member_id 会员
 * @property int $project_id 项目
 * @property string $investment_amount 投资金额
 * @property string $income_amount_all 已获收益
 * @property string $add_income
 * @property int $settlement_times 结算次数
 * @property int $status 状态
 * @property int $created_at 投资日期
 * @property int $next_time 下次结算时间
 * @property int $updated_at 到期时间
 */
class Bill extends \yii\db\ActiveRecord
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
        return 'dk_bill';
    }


    /**
     * 投资状态
     * @var array
     * @author 哈哈
     */
    public static $statusArray = [
        1 => "待盈利",
        2 => "盈利中",
        3 => "已结束",
    ];


    /**
     * 颜色
     * @var array
     * @author 哈哈
     */
    public static $statusColor = [
        1 => "primary",
        2 => "success",
        3 => "warning",
    ];



    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['member_id', 'project_id', 'investment_amount', 'created_at', 'next_time', 'updated_at'], 'required'],
            [['member_id', 'project_id', 'settlement_times', 'status', 'created_at', 'next_time', 'updated_at'], 'integer'],
            [['investment_amount', 'income_amount_all','add_income'], 'number'],
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
            'project_id' => '项目',
            'investment_amount' => '投资金额',
            'income_amount_all' => '已获收益',
            'settlement_times' => '结算次数',
            'status' => '状态',
            'created_at' => '投资日期',
            'next_time' => '下次结算时间',
            'updated_at' => '到期时间',
            'add_income' => '额外收益率',
        ];
    }

    /**
     * 关联订单
     * @return \yii\db\ActiveQuery
     */
    public function getDetail()
    {
        return $this->hasMany(Detail::class, ['b_id' => 'id']);
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
     * 关联项目表
     * @return \yii\db\ActiveQuery
     * @author 哈哈
     */
    public function getProject()
    {
        return $this->hasOne(Project::class, ['id' => 'project_id']);
    }

}
