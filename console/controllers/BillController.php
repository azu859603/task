<?php

namespace console\controllers;

use common\helpers\BcHelper;
use common\models\common\Statistics;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use common\models\tea\Bill;
use common\models\tea\Detail;
use yii\console\Controller;
use Yii;
class BillController extends Controller
{
    public function actionTest(){
//        $arr = ["a","b","c","d","e"];
        $arr = ["a","b"];
        var_dump($arr[count($arr)-2]);exit;
    }

    // 结算
    public function actionIndex()
    {
        // 解除内存限制
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $models = Bill::find()
            ->where(['<=', 'next_time', time()])
            ->andWhere(['<', 'status', 3])
            ->andWhere(['<>', 'settlement_times', 0])
            ->with([
                'project',
                'member' => function ($query) {
                    $query->with(['memberLevel', 'account']);
                },
            ])
            ->all();
        foreach ($models as $v1) {
            //开启事务
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $now_time =$v1->next_time;
                $v1->settlement_times -=1;
                if($v1->settlement_times==0){
                    $v1->status = 3;
                }else{
                    $v1->status = 2;
                    // 下次结算时间
                    $v1->next_time = $v1->next_time + (24 * 60 * 60);
                }

                // 首先退本金
                $today_benjin = BcHelper::div($v1->investment_amount,$v1->project->deadline);

                Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                    'member' => Member::findOne($v1->member->id),
                    'pay_type' => CreditsLog::FB_TYPE,
                    'num' => $today_benjin,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => "【退本】购买产品：" . $v1->project->title . "，周期结束退还本金" . $today_benjin . "元",
                    'map_id' => $v1->id,
                ]));

                $v1->member->account->investment_doing_money = BcHelper::sub($v1->member->account->investment_doing_money, $today_benjin);
                $v1->member->account->save(false);
                // 判断在投金额,如果等于0,说明用户未投了,用户投资状态变成2
                if ($v1->member->account->investment_doing_money == 0) {
                    $v1->member->investment_status = 2;
                    $v1->member->save(false);
                }
                // 给收益 ,判断本轮是否已完成打卡审核
                $end = $now_time;
                $start = $end - 86400;
                $today = ['start' => $start, 'end' => $end];
                if (Detail::find()->where(['b_id' => $v1['id'], 'member_id' => $v1['member_id'], 'status' => 1])->andWhere(['between', 'created_at', $today['start'], $today['end']])->exists()) {
                    //若完成了，给收益
                    $income = BcHelper::mul($v1->investment_amount, BcHelper::add(BcHelper::div($v1->project->income, 100, 4), BcHelper::div($v1->add_income, 100, 4), 4));
                    $v1->income_amount_all = BcHelper::add($v1->income_amount_all, $income);
                    // 添加收益
                    Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                        'member' => Member::findOne($v1->member->id),
                        'pay_type' => CreditsLog::INCOME_TYPE,
                        'num' => $income,
                        'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                        'remark' => "【产品收益】购买产品：" . $v1->project->title . "，获得收益" . $income . "元",
                        'map_id' => $v1->id,
                    ]));
                    // 添加个人统计
                    $v1->member->account->investment_income = BcHelper::add($v1->member->account->investment_income, $income);
                    $v1->member->account->save(false);
                    // 加入收益统计
                    Statistics::updateIncomeMoney(date('Y-m-d'), $income);
                }
                $v1->save(false);
                $transaction->commit();
            } catch (\Exception $e) {
                $transaction->rollback();
                return false;
            }
        }
        $this->stdout(date('Y-m-d H:i:s') . " ------ Automatic Settlement Income" . PHP_EOL);
    }
}