<?php


namespace api\modules\v1\controllers\common;


use api\controllers\OnAuthController;
use backend\modules\member\forms\RechargeForm;
use common\enums\StatusEnum;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\common\LotterySetting;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use yii\data\ActiveDataProvider;
use Yii;
use yii\web\UnprocessableEntityHttpException;

class LotteryController extends OnAuthController
{
    public $modelClass = LotterySetting::class;

    protected $authOptional = ['index'];

    /**
     * 奖品列表
     * @return array|ActiveDataProvider|\yii\db\ActiveRecord[]
     */
    public function actionIndex()
    {
        return LotterySetting::find()
            ->where(['status' => StatusEnum::ENABLED])
            ->select(['id', 'lottery_name', 'lottery_amount', 'proportion', 'banner', 'type', 'title'])
            ->orderBy(['sort' => SORT_ASC, 'created_at' => SORT_DESC])
            ->asArray()
            ->all();
    }


    /**
     * 开始摇奖
     * @return array|mixed|\yii\db\ActiveRecord
     * @throws UnprocessableEntityHttpException
     */
    public function actionCreate1()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        if (!Yii::$app->debris->config('lottery_switch')) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "抽奖功能已关闭！");
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $member_sql = "select * from rf_member where `id`={$this->memberId} and `lottery_number` > 0 for update";
            $member_lock = Yii::$app->db->createCommand($member_sql)->queryOne();
            if (empty($member_lock)) {
                throw new UnprocessableEntityHttpException('您今天的抽奖次数已用完！');
            }
            $member = Member::find()->where(['id' => $member_lock['id']])->with(['account'])->one();
            if ($member->free_lottery_number > 0) {
                $member->lottery_number -= 1;
                $member->free_lottery_number -= 1;
                $message = "本次抽奖免费，今日剩余免费摇奖次数" . $member->free_lottery_number . "次，今日剩余摇奖总次数" . $member->lottery_number . "次！";
            } else {
                $member->lottery_number -= 1;
                // 拿出配置消耗数量和消耗类型 进行扣除
                $lottery_type = Yii::$app->debris->config('lottery_type');
                $use_lottery_number = Yii::$app->debris->config('use_lottery_number');
                if ($lottery_type == 1) {
                    if ($use_lottery_number > $member->account->user_money) {
                        throw new UnprocessableEntityHttpException('余额不足,请先充值！');
                    }
                    $message = "本次抽奖将扣除余额" . $use_lottery_number . "元，今日剩余摇奖总次数" . $member->lottery_number . "次！";
                    Yii::$app->services->memberCreditsLog->decrMoney(new CreditsLogForm([
                        'member' => $member,
                        'num' => $use_lottery_number,
                        'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                        'remark' => "【摇奖】消耗余额",
                        'pay_type' => CreditsLog::LOTTERY_TYPE,
                    ]));
                } else {
                    if ($use_lottery_number > $member->account->user_integral) {
                        throw new UnprocessableEntityHttpException('积分不足！');
                    }
                    $message = "本次抽奖将扣除积分" . $use_lottery_number . "点，今日剩余摇奖总次数" . $member->lottery_number . "次！";
                    Yii::$app->services->memberCreditsLog->decrInt(new CreditsLogForm([
                        'member' => $member,
                        'num' => $use_lottery_number,
                        'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                        'remark' => "【摇奖】消耗积分",
                        'pay_type' => CreditsLog::LOTTERY_TYPE,
                    ]));
                }
            }
            $member->save(false);
            // 扣款结束，开始计算中奖
            $number = $this->get_rand();
            $lotterySetting = LotterySetting::findOne($number);
            if ($lotterySetting->type == 1 && $lotterySetting->lottery_amount != 0) {
                Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                    'member' => Member::findOne($member_lock['id']),
                    'num' => $lotterySetting->lottery_amount,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => "【摇奖】幸运大转盘抽中" . $lotterySetting->lottery_name,
                    'pay_type' => CreditsLog::LOTTERY_TYPE,
                ]));
            } elseif ($lotterySetting->type == 2) {
                Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                    'member' => Member::findOne($member_lock['id']),
                    'num' => $lotterySetting->lottery_amount,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => "【摇奖】幸运大转盘抽中" . $lotterySetting->lottery_name,
                    'pay_type' => CreditsLog::LOTTERY_TYPE,
                ]));
            }
            $transaction->commit();
            return ResultHelper::json(ResultHelper::SUCCESS_CODE, $message, ['id' => $number]);
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ResultHelper::json(ResultHelper::ERROR_CODE, $e->getMessage());
        }
    }


    /**
     * 开始摇奖
     * @return array|mixed|\yii\db\ActiveRecord
     * @throws UnprocessableEntityHttpException
     */
    public function actionCreate()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        if (!Yii::$app->debris->config('lottery_switch')) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "抽奖功能已关闭！");
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $member_sql = "select * from rf_member where `id`={$this->memberId} and `free_lottery_number` > 0 for update";
            $member_lock = Yii::$app->db->createCommand($member_sql)->queryOne();
            if (empty($member_lock)) {
                throw new UnprocessableEntityHttpException('您的抽奖次数已用完！');
            }
            $member = Member::find()->where(['id' => $member_lock['id']])->one();
            $member->free_lottery_number -= 1;
            $member->save(false);
            $number = $this->get_rand();
            $lotterySetting = LotterySetting::findOne($number);
//            if ($lotterySetting->lottery_amount > 0) {
//                Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
//                    'member' => Member::findOne($member_lock['id']),
//                    'num' => $lotterySetting->lottery_amount,
//                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
//                    'remark' => "【摇奖】幸运大转盘抽中" . $lotterySetting->lottery_name,
//                    'pay_type' => CreditsLog::LOTTERY_TYPE,
//                ]));
//            }
            if ($lotterySetting->type == 1 && $lotterySetting->lottery_amount != 0) {
                Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                    'member' => Member::findOne($member_lock['id']),
                    'num' => $lotterySetting->lottery_amount,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => "【摇奖】幸运大转盘抽中" . $lotterySetting->title,
                    'pay_type' => CreditsLog::LOTTERY_TYPE,
                ]));
            } elseif ($lotterySetting->type == 2) {
                Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                    'member' => Member::findOne($member_lock['id']),
                    'num' => 0,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => "【摇奖】幸运大转盘抽中" . $lotterySetting->title,
                    'pay_type' => CreditsLog::LOTTERY_TYPE,
                ]));
            }
            $transaction->commit();
            return ResultHelper::json(ResultHelper::SUCCESS_CODE, "OK", ['id' => $number]);
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ResultHelper::json(ResultHelper::ERROR_CODE, $e->getMessage());
        }
    }

    /**
     * 计算概率
     * @return int|string
     */
    private function get_rand()
    {
        $proArr = \yii\helpers\ArrayHelper::map(LotterySetting::find()
            ->where(['status' => StatusEnum::ENABLED])
            ->select(['id', 'proportion'])
            ->orderBy(['proportion' => SORT_DESC])
            ->asArray()
            ->all(), 'id', 'proportion');
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);
        return $result;
    }

    /**
     * 权限验证
     * @param string $action
     * @param null $model
     * @param array $params
     * @throws \yii\web\BadRequestHttpException
     * @author 原创脉冲
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        // 方法名称
        if (in_array($action, ['view', 'update', 'delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }


}