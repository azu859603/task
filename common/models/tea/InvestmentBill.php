<?php

namespace common\models\tea;

use common\enums\StatusEnum;
use common\helpers\CommonPluginHelper;
use common\models\member\Account;
use common\models\member\Member;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "t_investment_bill".
 *
 * @property int $id
 * @property string $sn 订单号
 * @property int $category 产品分类(1按天发放,2按月发放,3按周期发放)
 * @property int $member_id 会员id
 * @property int $project_id 项目id
 * @property string $investment_amount 投资金额
 * @property int $settlement_times 结算次数
 * @property string $income_amount_all 项目收益(总)
 * @property string $income_amount 额外收益率
 * @property string $additional_income 额外收益
 * @property string $add_income 额外收益
 * @property int $status 分成状态(1,未开始,2,已开始,3,已停止)
 * @property int $created_at 投资日期
 * @property int $next_time 下次结算时间
 * @property int $updated_at 到期时间
 * @property string $remark 合同备注
 * @property int $ch_id
 * @property int $cj_id
 * @property string $send_name
 * @property string $send_mobile
 * @property string $send_address
 * @property string $send_remark
 * @property int $send_status
 */
class InvestmentBill extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 't_investment_bill';
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
     * 投资状态
     * @var array
     * @author 哈哈
     */
    public static $statusArray = [
        1 => "待盈利",
        2 => "盈利中",
        3 => "已结束",
        4 => "已停结",
    ];

    public static $sendStatusArray = [
        0 => "不赠送",
        1 => "未发货",
        2 => "已发货",
    ];

    public static $statusColorExplain = [
        0 => "info",
        1 => "danger",
        2 => "success",
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
        4 => "danger"
    ];

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sn', 'category', 'member_id', 'project_id', 'investment_amount', 'settlement_times', 'created_at', 'next_time', 'updated_at'], 'required'],
            [['id', 'category', 'member_id', 'project_id', 'settlement_times', 'status', 'created_at', 'next_time', 'updated_at', 'ch_id', 'cj_id', 'send_status'], 'integer'],
            [['investment_amount', 'income_amount_all', 'income_amount', 'additional_income', 'add_income'], 'number'],
            [['sn', 'send_name', 'send_mobile', 'send_remark'], 'string', 'max' => 50],
            [['send_address'], 'string', 'max' => 100],
            [['remark'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sn' => '订单号',
            'category' => '产品分类',
            'member_id' => '会员ID',
            'project_id' => '产品ID',
            'investment_amount' => '购买金额',
            'settlement_times' => '结算次数',
            'income_amount' => '应结算',
            'income_amount_all' => '已获收益',
            'status' => '状态',
            'created_at' => '购买日期',
            'next_time' => '下次结算',
            'updated_at' => '到期时间',
            'additional_income' => '额外收益',
            'add_income' => '额外收益率',
            'remark' => '合同备注',
            'send_name' => '收货人',
            'send_mobile' => '电话',
            'send_address' => '地址',
            'send_remark' => '快递单号',
            'send_status' => '状态',
        ];
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if ($this->isNewRecord) {
            $this->sn = CommonPluginHelper::getSn($this->member_id);
        }

        return parent::beforeSave($insert);
    }

    /**
     * 关联项目表
     * @return \yii\db\ActiveQuery
     * @author 哈哈
     */
    public function getInvestmentProject()
    {
        return $this->hasOne(InvestmentProject::class, ['id' => 'project_id']);
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
     * 管理账户信息
     * @return \yii\db\ActiveQuery
     */
    public function getAccount()
    {
        return $this->hasOne(Account::class, ['member_id' => 'member_id']);
    }

    /**
     * 获取项目投资次数
     * @param $id
     * @param $member_id
     * @return int|string
     * @author 哈哈
     */
    public static function getInvestmentBillCount($id, $member_id)
    {
        return self::find()
            ->select(['id'])
            ->where(['member_id' => $member_id, 'project_id' => $id])
            ->count();
    }

    /**
     * 项目详情
     * @param $id
     * @return array|null|ActiveRecord
     */
    public static function getModelById($id)
    {
        $model = self::find()
            ->select([
                'id',
                'category',
                'add_income',
                'sn',
                'member_id',
                'project_id',
                'investment_amount',
                'income_amount_all',
                'FROM_UNIXTIME(`created_at`,\'%Y-%m-%d %H:%i:%s\') as created_at',
                'FROM_UNIXTIME(`updated_at`,\'%Y-%m-%d %H:%i:%s\') as updated_at',
                'remark',
            ])
            ->where(['id' => $id])
            ->with(['member' => function ($query) {
                $query->select(['realname', 'identification_number']);
            }])
            ->with(['investmentProject' => function ($query) {
                $query->select(['category', 'title', 'deadline', 'income']);
            }])
            ->asArray()
            ->one();
        $model['my_company_name'] = Yii::$app->debris->config('my_company_name');
        $model['company_seal'] = Yii::$app->debris->config('company_seal');
        $model['guarantee'] = Yii::$app->debris->config('guarantee');
        $model['guarantee_seal'] = Yii::$app->debris->config('guarantee_seal');
        return $model;
    }

    // 关联赠送红包优惠价
    public function getCh()
    {
        return $this->hasOne(CouponMember::class, ['id' => 'ch_id']);
    }

    // 关联赠送加息优惠价
    public function getCj()
    {
        return $this->hasOne(CouponMember::class, ['id' => 'cj_id']);
    }
}
