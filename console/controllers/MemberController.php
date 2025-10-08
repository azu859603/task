<?php
// +----------------------------------------------------------------------------------------
// | 原创项目
// +----------------------------------------------------------------------------------------
// | 版权所有 原创脉冲工作室
// +----------------------------------------------------------------------------------------
// |  联系方式：
// |  QQ：2790684490
// |  skype：live:.cid.3adbd0e19c228153
// |  Telegram：@coderleo
// +----------------------------------------------------------------------------------------
// | 开发团队:原创脉冲
// +----------------------------------------------------------------------------------------

namespace console\controllers;


use common\helpers\DateHelper;
use common\models\member\CreditsLog;
use common\models\member\Member;
use yii\console\Controller;
use Yii;

class MemberController extends Controller
{
    /**
     * 更新用户签到状态
     */
    public function actionUpdateSignStatus()
    {
        // 解除内存限制
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        // 开始更新
        $free_lottery_number = Yii::$app->debris->config('free_lottery_number');
        $lottery_number = Yii::$app->debris->config('lottery_number');
        Member::updateAll([
            'sign_status' => 0,
//            'free_lottery_number' => $free_lottery_number,
//            'lottery_number' => $lottery_number,
        ]);
        $this->stdout(date('Y-m-d H:i:s') . " ------ Update Member Sign Status Is Ok" . PHP_EOL);
    }

    /**
     * 更新用户连续签到天数
     */
    public function actionUpdateSignDay()
    {
        // 解除内存限制
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $member = Member::find()
            ->where(['>', 'sign_days', 0])
            ->with('yesterdaySign')
            ->all();
        foreach ($member as $k1 => $v1) {
            if (empty($v1->yesterdaySign)) {
                $v1->sign_days = 0;
                $v1->save(false);
            }
        }
        $this->stdout(date('Y-m-d H:i:s') . " ------ Update Member Sign Day Is Ok" . PHP_EOL);
    }
}