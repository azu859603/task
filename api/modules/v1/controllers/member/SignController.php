<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2020/7/8
 * Time: 18:56
 */

namespace api\modules\v1\controllers\member;


use api\controllers\OnAuthController;
use common\helpers\BcHelper;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\common\Statistics;
use common\models\forms\CreditsLogForm;
use common\models\member\Account;
use common\models\member\CreditsLog;
use common\models\member\Member;
use common\models\member\MemberCard;
use common\models\member\SignIn;
use Yii;
use yii\base\BaseObject;
use yii\web\UnprocessableEntityHttpException;

class SignController extends OnAuthController
{
    public $modelClass = '';

    /**
     * 签到
     * @return array|mixed|\yii\db\ActiveRecord
     * @throws UnprocessableEntityHttpException
     */
    public function actionCreate()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        // 签到开关 1开0关 签到赠送数量
        if (!Yii::$app->debris->config('check_in_switch')) {
            throw new UnprocessableEntityHttpException('签到功能暂未开放');
        }
        // 判断用户当天已签到次数
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $member_sql = "select * from rf_member where `id`={$this->memberId} and `sign_status` = 0 for update";
            $member_lock = Yii::$app->db->createCommand($member_sql)->queryOne();
            if (empty($member_lock)) {
                throw new UnprocessableEntityHttpException('您今天已经完成签到任务');
            }
            $member = Member::find()->where(['id' => $member_lock['id']])->with(['memberLevel'])->one();
//            if (empty($member->realname)) {
//                throw new UnprocessableEntityHttpException('请您先实名认证后再继续操作');
//            }
//            if ($member->memberLevel->sign_gift_number <= 0 && $member->memberLevel->sign_gift_money <= 0) {
//                throw new UnprocessableEntityHttpException('签到功能暂未开放');
//            }

//            if ($member->memberLevel->sign_gift_number > 0) {
//                Yii::$app->services->memberCreditsLog->incrInt(new CreditsLogForm([
//                    'member' => $member,
//                    'pay_type' => CreditsLog::SIGN_TYPE,
//                    'num' => $member->memberLevel->sign_gift_number,
//                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
//                    'remark' => "【系统】签到赠送积分",
//                ]));
//            }
            if ($member->memberLevel->sign_gift_money > 0) {
//                Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
//                    'member' => $member,
//                    'pay_type' => CreditsLog::SIGN_TYPE,
//                    'num' => $member->memberLevel->sign_gift_money,
//                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
//                    'remark' => "【系统】签到赠送奖金",
//                ]));
                $member->account->experience = BcHelper::add($member->account->experience, $member->memberLevel->sign_gift_money, 0);
                $member->account->save(false);
                Yii::$app->services->memberLevel->updateLevel($member);
            }
//            else {
//                throw new UnprocessableEntityHttpException('签到功能暂未开放');
//            }
            $member->sign_status = 1;
            $member->sign_days += 1;
            $member->save(false);
            // 加入记录
            $model = new SignIn();
            $model->member_id = $this->memberId;
            $model->save();

            // 签到赠送余额
            if (Yii::$app->params['thisAppEnglishName'] == "task_cn") {
                $days = BcHelper::mod($member->sign_days, 30, 0);
                if ($days == 0) {
                    $money = 3;
                } else {
                    $money = BcHelper::div($days, 10);
                }

                Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                    'member' => $member,
                    'pay_type' => CreditsLog::SIGN_TYPE,
                    'num' => $money,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => "【系统】签到赠送奖金",
                ]));


            }
            // 加入统计表
            Statistics::updateSignMember(date("Y-m-d"));

            $transaction->commit();
            return ResultHelper::json(ResultHelper::SUCCESS_CODE, "签到成功");
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ResultHelper::json(ResultHelper::ERROR_CODE, $e->getMessage());
        }
    }
}