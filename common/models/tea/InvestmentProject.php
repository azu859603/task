<?php

namespace common\models\tea;

use common\enums\StatusEnum;
use common\helpers\DateHelper;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "t_investment_project".
 *
 * @property int $id
 * @property int $category 产品分类(1按天发放,2按月发放,3按周期发放,4按天复利)
 * @property string $title 产品名称
 * @property string $project_img 产品图片
 * @property int $all_investment_amount 项目规模(元)/总额
 * @property int $can_investment_amount 可投金额(元)
 * @property string $schedule 项目进度(百分比)
 * @property int $least_amount 起投金额(元)
 * @property int $most_amount 最高可投金额(0,不限制)
 * @property int $limit_times 限制次数(0,不限制)
 * @property int $investment_number 已投资人数
 * @property int $deadline 项目期限(多少个自然日)
 * @property string $income 收益(百分比)
 * @property int $status 项目状态(1投资中,0投资已满)
 * @property int $project_status 状态
 * @property int $sort 排序
 * @property int $created_at 添加时间
 * @property int $gift_method 红包类型
 * @property int $lottery_number 抽奖次数
 * @property int $spike_type 秒杀类型
 * @property int $prize_type 奖品赠送
 * @property int $spike_start_time 秒杀开始时间
 * @property int $spike_stop_time  秒杀结束时间
 * @property int $type  类型
 * @property int $increase_status  项目进度自动增长状态
 * @property int $increase_times  每次自动增长投资份数
 * @property string $gift_amount 赠送金额
 * @property string $gift_instruction 红包赠送说明
 * @property string $lottery_instruction 抽奖赠送说明
 * @property string $prize_instruction 奖品赠送说明
 * @property string $project_detail 项目详情
 * @property string $integral_percentage 可获积分（百分比）
 * @property string $parent_integral_percentage 可获积分（百分比）
 * @property int $return_method 返现类型
 * @property string $return_percentage 返现金额百分比
 * @property string $return_instruction 返现说明
 * @property int $my_get_number 自己投资能获得的虎卡数量
 * @property int $one_get_number 自己投资上级能获得的虎卡数量
 * @property string $describe 项目描述
 * @property string $commission_one 一级返佣
 * @property string $commission_two 二级返佣
 * @property string $remark 备注
 * @property int $home_show_switch 首页是否展示
 * @property int $ch_id
 * @property int $cj_id
 * @property int $vip_level
 * @property string $experience_multiple
 * @property int $send_gift_switch
 * @property string $project_superior_rebate
 * @property int $project_superior_rebate_time
 * @property int $gift_amount_time
 * @property int $parent_lottery_number
 *
 */
class InvestmentProject extends \yii\db\ActiveRecord
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
        return 't_investment_project';
    }

    /**
     * 赠送方式
     * @var array
     */
    public static $giftMethod = [
        0 => "不赠送",
        1 => "立购赠",
        2 => "结购赠",
    ];


    /**
     * 分类
     * @var array
     * @author 哈哈
     */
    public static $categoryArray = [
        1 => '每日付息',
//        2 => '每月付息',
        3 => '到期付息',
//        4 => '每日复利',
    ];

    /**
     * 类型
     * @var array
     * @author 哈哈
     */
    public static $typeArray = [
        1 => '福利活动区',
        2 => '新手专享区',
        3 => '常规优选区',
    ];


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category', 'title', 'all_investment_amount', 'can_investment_amount', 'deadline', 'income', 'type', 'commission_one', 'commission_two', 'integral_percentage','parent_integral_percentage'], 'required'],
            [['category', 'all_investment_amount', 'can_investment_amount', 'least_amount', 'most_amount', 'limit_times', 'investment_number', 'deadline', 'status', 'sort', 'created_at', 'gift_method', 'lottery_number', 'spike_type', 'prize_type', 'project_status', 'type', 'increase_status', 'increase_times', 'return_method', 'my_get_number', 'one_get_number', 'home_show_switch', 'ch_id', 'cj_id', 'vip_level', 'send_gift_switch','parent_lottery_number',], 'integer'],
            [['schedule', 'income', 'gift_amount', 'integral_percentage','parent_integral_percentage', 'return_percentage', 'commission_one', 'commission_two', 'experience_multiple', 'project_superior_rebate'], 'number', 'numberPattern' => '/^\d+(.\d{1,2})?$/', 'message' => '小数点后位数不能大于俩位'],
            [['title'], 'string', 'max' => 100],
            [['describe', 'remark'], 'string', 'max' => 255],
            [['project_img'], 'file', 'extensions' => 'png,jpg,jpeg,gif', 'mimeTypes' => 'image/jpeg, image/png, image/gif', 'maxSize' => 1024 * 1024 * 10, 'maxFiles' => 1],
            [['gift_instruction', 'lottery_instruction', 'prize_instruction', 'project_detail', 'return_instruction'], 'string'],
            [['spike_start_time'], 'datetime', 'timestampAttribute' => 'spike_start_time'],
            [['spike_stop_time'], 'datetime', 'timestampAttribute' => 'spike_stop_time'],
            [['project_superior_rebate_time'], 'datetime', 'timestampAttribute' => 'project_superior_rebate_time'],
            [['gift_amount_time'], 'datetime', 'timestampAttribute' => 'gift_amount_time'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category' => '产品分类',
            'title' => '产品名称',
            'project_img' => '产品图片(750X400)',
            'all_investment_amount' => '产品总额',
            'can_investment_amount' => '可购金额',
            'schedule' => '产品进度',
            'least_amount' => '起购金额',
            'most_amount' => '最高可购金额',
            'limit_times' => '限制次数',
            'investment_number' => '已购买人数',
            'deadline' => '产品期限',
            'income' => '日收益率',
            'status' => '产品状态',
            'sort' => '排序',
            'created_at' => '创建时间',
            'gift_method' => '红包类型',
            'gift_amount' => '红包金额',
            'gift_amount_time' => '赠送红包,当前用户注册时间',
            'lottery_number' => '赠送抽奖次数',
            'parent_lottery_number' => '上级赠送抽奖次数',
            'spike_type' => '秒杀类型',
            'prize_type' => '奖品赠送',
            'gift_instruction' => '红包赠送说明',
            'lottery_instruction' => '抽奖赠送说明',
            'prize_instruction' => '奖品赠送说明',
            'project_detail' => '产品详情',
            'project_status' => '状态',
            'spike_start_time' => '秒杀开始时间',
            'spike_stop_time' => '秒杀结束时间',
            'type' => '类型',
            'integral_percentage' => '可获积分百分比',
            'parent_integral_percentage' => '上级可获积分百分比',
            'increase_status' => '项目进度自动增长状态',
            'increase_times' => '每次自动增长投资份数',
            'return_method' => '返现类型',
            'return_percentage' => '返现金额百分比',
            'return_instruction' => '返现说明',
            'my_get_number' => '自己投资能获得的虎卡数量',
            'one_get_number' => '自己投资上级能获得的虎卡数量',
            'describe' => '项目描述',
            'commission_one' => '一级返佣',
            'commission_two' => '二级返佣',
            'remark' => '备注',
            'home_show_switch' => '首页是否展示',
            'ch_id' => '优惠券红包',
            'cj_id' => '优惠券加息',
            'vip_level' => 'VIP购买等级',
            'experience_multiple' => '经验倍数',
            'send_gift_switch' => '赠送礼品',
            'project_superior_rebate' => '上级返佣金额',
            'project_superior_rebate_time' => '上级返佣,下级用户注册时间',
        ];
    }

    /**
     * 关联当天投资订单表
     * @return \yii\db\ActiveQuery
     */
    public function getToDayInvestmentBill()
    {
        $today = DateHelper::today();
        return $this->hasMany(InvestmentBill::class, ['project_id' => 'id'])->where(['between', 'created_at', $today['start'], $today['end']]);
    }

    /**
     * 关联当月投资订单表
     * @return \yii\db\ActiveQuery
     */
    public function getToMonthInvestmentBill()
    {
        $month = DateHelper::thisMonth();
        return $this->hasMany(InvestmentBill::class, ['project_id' => 'id'])->where(['between', 'created_at', $month['start'], $month['end']]);
    }

    /**
     * 关联所以投资订单表
     * @return \yii\db\ActiveQuery
     */
    public function getAllInvestmentBill()
    {
        return $this->hasMany(InvestmentBill::class, ['project_id' => 'id']);
    }

    public function getCategoryList()
    {
        return $this->hasOne(CategoryList::class, ['id' => 'type']);
    }

    // 关联赠送红包优惠价
    public function getCh()
    {
        return $this->hasOne(CouponList::class, ['id' => 'ch_id'])->where(['status' => StatusEnum::ENABLED]);
    }

    // 关联赠送加息优惠价
    public function getCj()
    {
        return $this->hasOne(CouponList::class, ['id' => 'cj_id'])->where(['status' => StatusEnum::ENABLED]);
    }
}
