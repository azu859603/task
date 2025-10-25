<?php

namespace common\models\task;

use common\helpers\BcHelper;
use common\models\common\Statistics;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "task_order".
 *
 * @property int $id
 * @property int $member_id 会员
 * @property int $pid 任务
 * @property int $status 状态
 * @property int $created_at 添加时间
 * @property int $updated_at 完成时间
 * @property string $video_url 视频地址
 * @property array $images_list 任务截图
 * @property string $money 任务佣金
 * @property string $code 活动码
 * @property int $push_number
 * @property int $cid
 * @property int $updated_by
 * @property string $remark
 */
class Order extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task_order';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['member_id', 'pid', 'created_at'], 'required'],
            [['member_id', 'pid', 'status', 'created_at', 'updated_at', 'push_number', 'cid','updated_by'], 'integer'],
            [['images_list'], 'safe'],
            [['money'], 'number'],
            [['video_url', 'code', 'remark'], 'string', 'max' => 255],
        ];
    }

    public static $statusExplain = [0 => '待提交', 1 => "已提交", 2 => "已通过", 3 => "已驳回"];


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'member_id' => '会员',
            'pid' => '任务',
            'status' => '状态',
            'created_at' => '添加时间',
            'updated_at' => '完成时间',
            'updated_by' => '审核人',
            'video_url' => '视频地址',
            'images_list' => '任务截图',
            'money' => '任务佣金',
            'code' => '活动码',
            'push_number' => '已提交次数',
            'remark' => '备注',
            'cid' => '任务类型',
        ];
    }


    /**
     * @param bool $insert
     * @return bool
     * @throws \yii\base\Exception
     */
    public function beforeSave($insert)
    {
        // 修改
        if (!$this->isNewRecord) {
            // 如果是通过 则增加账户余额
            if ($this->isAttributeChanged('status') && $this->status == 2) {
                // 充值成功 用户本金增加
                $member = Member::find()->where(['id' => $this->member_id])->with(['account'])->one();
                $member->account->investment_number += 1;
                if ($this->money > 0) {
                    $member->account->investment_income = BcHelper::add($member->account->investment_income, $this->money);
                    Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                        'member' => $member,
                        'num' => $this->money,
                        'credit_group' => CreditsLog::CREDIT_GROUP_MANAGER,
                        'remark' => '【任务】完成任务获得佣金',
                        'pay_type' => CreditsLog::TASK_TYPE
                    ]));
                }

                // 若有活动码，从中取值
                $project = Project::find()->where(['id' => $this->pid])->one();
                if ($project->code_switch == 1) {
                    $code = TaskCode::find()->where(['status' => 0])->orderBy(['id' => SORT_ASC])->one();
                    if (!empty($code)) {
                        $this->code = $code->code;
                        $code->member_id = $this->member_id;
                        $code->t_id = $this->id;
                        $code->status = 1;
                        $code->save(false);
                    }
                }

                // 如果任务有经验，添加经验
                if ($project->experience > 0) {
                    $member->account->experience = BcHelper::add($member->account->experience,$project->experience,0);
                }
                $member->account->save(false);
                Yii::$app->services->memberLevel->updateLevel($member);

                // 加入统计表
                if ($member['type'] == 1) {
                    // 加入统计表 获取最上级用户ID
                    $first_member = Member::getParentsFirst($member);
                    $b_id = $first_member['b_id'] ?? 0;
                    Statistics::updateOverTask(date("Y-m-d"), $this->money, $b_id);
                }
                // 上级获得奖金
                $get_task_money = Yii::$app->debris->backendConfig('get_task_money');
                if (!empty($member->pid) && $get_task_money > 0) {
                    Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                        'member' => Member::findOne($member->pid),
                        'pay_type' => CreditsLog::BUY_SHORT_PLAYS_TYPE,
                        'num' => $get_task_money,
                        'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                        'remark' => "【返佣】下级完成任务获得佣金",
                    ]));
                }
            }
        }
        return parent::beforeSave($insert);
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
     * 关联用户表
     * @return \yii\db\ActiveQuery
     * @author 哈哈
     */
    public function getProject()
    {
        return $this->hasOne(Project::class, ['id' => 'pid']);
    }

    /**
     * 关联用户表
     * @return \yii\db\ActiveQuery
     * @author 哈哈
     */
    public function getManager()
    {
        return $this->hasOne(\common\models\backend\Member::class, ['id' => 'updated_by']);
    }
}
