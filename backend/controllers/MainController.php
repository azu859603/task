<?php

namespace backend\controllers;

use common\helpers\ArrayHelper;
use common\models\backend\Member;
use common\models\base\SearchModel;
use common\models\common\Statistics;
use common\models\tea\InvestmentProject;
use Yii;
use backend\forms\ClearCache;
use common\helpers\ResultHelper;

/**
 * 主控制器
 *
 * Class MainController
 * @package backend\controllers
 * @author 原创脉冲
 */
class MainController extends BaseController
{

    /**
     * 默认数据
     *
     * @var array
     */
    public $attention = [
        'register_member' => 0,
        'sign_member' => 0,
        'withdraw_money' => 0,
        'recharge_money' => 0,
        'commission_money' => 0,
        'income_money' => 0,
        'new_investment_number' => 0,
        'new_investment_money' => 0,
        'stop_investment_number' => 0,
        'stop_investment_money' => 0,
        'buy_money' => 0,
        'recharge_number' => 0,
        'withdraw_number' => 0,
        'get_task_number' => 0,
        'over_task_number' => 0,
    ];

    /**
     * 子框架默认主页
     * @return string
     */
    public function actionSystem()
    {
        // 解除内存限制
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $request = Yii::$app->request;

        $member_id = Yii::$app->user->getId();


        if ($member_id == 1) {
            $memberIds = \yii\helpers\ArrayHelper::map(Member::find()
                ->select(['id', 'username'])
                ->where(['<>', 'id', 1])
                ->asArray()->all(), 'id', 'username');
            $memberIds[0] = "全部";
            ksort($memberIds);
            $memberId = $request->get('memberId', 0);
            if ($memberId == 0) {
                $member_id_where = [];
            } else {
                $member_id_where = ['member_id' => $memberId];
            }
        } else {
            $member_id_where = ['member_id' => $member_id];
            $memberIds[Yii::$app->user->getId()] = Yii::$app->user->identity->username;
            $memberId = Yii::$app->user->getId();
        }

        $from_date = $request->get('from_date', date('Y-m-d', strtotime("-30 day")));
        $to_date = $request->get('to_date', date('Y-m-d', strtotime("+30 day")));
        $models = Statistics::findBetweenByDate($from_date, $to_date, $memberId);

        // 今日统计
        $today = $this->attention;
        if ($todayModel = Statistics::findByDate(date('Y-m-d'), $member_id_where)) {
            $today = ArrayHelper::merge($this->attention, $todayModel);
        }

        // 本月统计
        $to_month = $this->attention;
        if ($to_monthModel = Statistics::findByMonth($member_id_where)) {
            $to_month = ArrayHelper::merge($this->attention, $to_monthModel);
        }

        // 所有日期累积统计
        $all_day = $this->attention;
        if ($allDayModel = Statistics::findByDateAll($member_id_where)) {
            $all_day = ArrayHelper::merge($this->attention, $allDayModel);
        }


        return $this->render($this->action->id, [
            'models' => $models,
            'all_day' => $all_day,
            'today' => $today,
            'to_month' => $to_month,
            'from_date' => $from_date,
            'to_date' => $to_date,
            'memberIds' => $memberIds,
            'memberId' => $memberId,
        ]);
    }

    /**
     * 子框架默认主页1
     *
     * @return string
     */
    public function actionSystem1()
    {
        $merchant_id = Yii::$app->services->merchant->getId();

        return $this->render($this->action->id, [
            'memberCount' => Yii::$app->services->member->getCount($merchant_id),
            'memberAccount' => Yii::$app->services->memberAccount->getSum($merchant_id),
        ]);
    }

    /**
     * 系统首页
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->renderPartial($this->action->id, [
        ]);
    }


    /**
     * 用户指定时间内数量
     *
     * @param $type
     * @return array
     */
    public function actionMemberBetweenCount($type)
    {
        $data = Yii::$app->services->member->getBetweenCountStat($type);

        return ResultHelper::json(200, '获取成功', $data);
    }

    /**
     * 充值统计
     *
     * @param $type
     * @return array
     */
    public function actionMemberRechargeStat($type)
    {
        $data = Yii::$app->services->memberCreditsLog->getRechargeStat($type);

        return ResultHelper::json(200, '获取成功', $data);
    }

    /**
     * 提现统计
     *
     * @param $type
     * @return array
     */
    public function actionMemberWithdrawStat($type)
    {
        $data = Yii::$app->services->memberCreditsLog->getWithdrawStat($type);

        return ResultHelper::json(200, '获取成功', $data);
    }

    /**
     * 用户指定时间内消费日志
     *
     * @param $type
     * @return array
     */
    public function actionMemberCreditsLogBetweenCount($type)
    {
        $data = Yii::$app->services->memberCreditsLog->getBetweenCountStat($type);

        return ResultHelper::json(200, '获取成功', $data);
    }

    /**
     * 清理缓存
     *
     * @return string
     */
    public function actionClearCache()
    {
        $model = new ClearCache();
        if ($model->load(Yii::$app->request->post())) {
            return $model->save()
                ? $this->message('清理成功', $this->refresh())
                : $this->message($this->getError($model), $this->refresh(), 'error');
        }

        return $this->render($this->action->id, [
            'model' => $model
        ]);
    }
}