<?php

namespace api\modules\v1\controllers\tea;

use api\controllers\OnAuthController;
use common\enums\StatusEnum;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use common\models\tea\Activity;
use common\models\tea\ActivityCardList;
use common\models\tea\ActivityCardSetting;
use common\models\tea\ActivityMoneySetting;
use Yii;
use yii\web\UnprocessableEntityHttpException;

class ActivityCardListController extends OnAuthController
{
    public $modelClass = '';

    /**
     * 卡片数量列表
     * @return array|\yii\data\ActiveDataProvider|\yii\db\ActiveRecord[]
     */
    public function actionIndex()
    {
        return ActivityCardSetting::find()
            ->where(['status' => StatusEnum::ENABLED])
            ->select(['id', 'type', 'banner1', 'banner2'])
            ->with(['cardList' => function ($query) {
                $query->select(['pid', 'count(*) as number'])->where(['status' => StatusEnum::DISABLED, 'member_id' => $this->memberId])->groupBy(['pid']);
            }])
            ->asArray()
            ->all();
    }

    /**
     * 卡片列表
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionList()
    {
        return ActivityCardList::find()
            ->where(['member_id' => $this->memberId])
            ->select(['remark'])
            ->orderBy(['created_at' => SORT_DESC])
            ->asArray()
            ->all();
    }

    /**
     * 合成五运卡
     * @return array|mixed|\yii\db\ActiveRecord
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionCreate()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        $list = ActivityCardSetting::find()
            ->where(['status' => StatusEnum::ENABLED, 'type' => 1])
            ->select(['id'])
            ->with(['cardList' => function ($query) {
                $query->select(['id', 'pid', 'count(*) as number'])->where(['status' => StatusEnum::DISABLED, 'member_id' => $this->memberId])->groupBy(['pid']);
            }])
            ->asArray()
            ->all();
        $card_ids = [];
        foreach ($list as $v) {
            if (empty($v['cardList']['id'])) {
                return ResultHelper::json(ResultHelper::ERROR_CODE, '五运虎合成所需卡片不足，请继续收集！');
            } else {
                $card_ids[] = $v['cardList']['id'];
            }
        }
        // 开启事务同步更新
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $card_list = ActivityCardList::find()->where(['member_id' => $this->memberId])->andWhere(['in', 'id', $card_ids])->all();
            foreach ($card_list as $cards) {
                $cards->status = StatusEnum::ENABLED;
                $cards->updated_at = time();
                $cards->remark = $cards->remark . "(已合成)";
                $cards->save();
            }
            $card = new ActivityCardList();
            $card->member_id = $this->memberId;
            $card->pid = ActivityCardSetting::find()->where(['type' => 2])->select(['id'])->one()['id'];
            $card->remark = "【五虎合一】五张虎卡合成了一张五运虎";
            $card->type = 4;
            $card->save();
            $transaction->commit();
            return ResultHelper::json(ResultHelper::SUCCESS_CODE, "恭喜您合成了五运虎，到开奖时间即可使用此卡开奖！");
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ResultHelper::json(ResultHelper::ERROR_CODE, "合成失败,请联系客服处理！");
        }
    }

    /**
     * 开奖
     * @return array|mixed
     * @throws UnprocessableEntityHttpException
     */
    public function actionOpen()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        // 活动类型ID
        $id = Yii::$app->request->post('id');
        if (empty($id)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "ID不能为空！");
        }
        $activity = Activity::findOne($id);
        if (empty($activity)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "活动不存在！");
        }
        if (time() < $activity['open_time'] || time() > $activity['stop_time']) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, $activity['title'] . "开奖时间段是" . date("Y-m-d H:i:s", $activity['open_time']) . "至" . date("Y-m-d H:i:s", $activity['stop_time']) . "，请在活动开奖期间点击开奖！");
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 首先看是否拥有五运卡
            $card_sql = "select * from t_activity_card_list where `member_id` = {$this->memberId} and `type` = 4 and `status` = 0 for update";
            $card_lock = Yii::$app->db->createCommand($card_sql)->queryOne();
            if (empty($card_lock)) {
                throw new UnprocessableEntityHttpException('您暂未拥有五运虎，请先收集后再来开奖！');
            }

            $card = ActivityCardList::findOne(['id' => $card_lock['id']]);
            $card->status = StatusEnum::ENABLED;
            $card->updated_at = time();
            $card->remark = $card->remark . "(已开奖)";
            $card->save();
            // 开始抽奖
            $number = $this->get_number();
            $moneySetting = ActivityMoneySetting::findOne($number);
            $money = $moneySetting['money'];
            Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                'member' => Member::findOne($this->memberId),
                'num' => $money,
                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                'remark' => "【集卡】" . $activity['title'] . "瓜分大奖，获得奖金" . $money . "元",
                'pay_type' => CreditsLog::JK_TYPE,
            ]));
            $transaction->commit();
            return ResultHelper::json(ResultHelper::SUCCESS_CODE, "获得" . $money . "元", ['money' => $money]);
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ResultHelper::json(ResultHelper::ERROR_CODE, $e->getMessage());
        }
    }

    /**
     * 赠送卡片
     */
    public function actionSend()
    {
        $mobile = Yii::$app->request->post('mobile');
        if (empty($mobile)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "请填写好友手机号！");
        }
        if (empty($send_member = Member::find()->where(['mobile' => $mobile])->select(['id'])->asArray()->one())) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "用户不存在！");
        }
        if ($send_member['id'] == $this->memberId) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "不能自己赠送自己！");
        }
        $id = Yii::$app->request->post('id');// 卡片类型ID=>PID
        if (empty($id)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "赠送类型ID不能为空！");
        }
        $cardSetting = ActivityCardSetting::find()
            ->where(['id' => $id, 'status' => StatusEnum::ENABLED, 'type' => 1])
            ->select(['id', 'title'])
            ->with(['cardList' => function ($query) {
                $query->select(['id', 'pid'])->where(['status' => StatusEnum::DISABLED, 'member_id' => $this->memberId]);
            }])
            ->asArray()
            ->one();
        if (empty($cardSetting['cardList']['id'])) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "卡片数量不足！");
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 赠送卡片
            $my_card = ActivityCardList::find()->where(['member_id' => $this->memberId, 'id' => $cardSetting['cardList']['id']])->one();
            $my_card->status = 1;
            $my_card->updated_at = time();
            $my_card->remark = $my_card->remark . "(已赠送)";
            $my_card->save();
            // 对方获得卡片
            $send_card = new ActivityCardList();
            $send_card->pid = $my_card->pid;
            $send_card->member_id = $send_member['id'];
            $send_card->remark = '【好友赠送】您的好友赠送了您一张' . $cardSetting['title'];
            $send_card->type = 3;
            $send_card->save();
            $transaction->commit();
            return ResultHelper::json(ResultHelper::SUCCESS_CODE, "赠送成功！");
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ResultHelper::json(ResultHelper::ERROR_CODE, $e->getMessage());
        }

    }

    /**
     * 获取奖项id
     */
    private function get_number()
    {
        $prize_arr = ActivityMoneySetting::find()->select(['id', 'proportion'])->where(['status' => 1])->asArray()->all();
        $arr = [];
        foreach ($prize_arr as $key => $val) {
            $arr[$val['id']] = $val['proportion'];
        }
        $rid = $this->get_rand($arr); //根据概率获取奖项id
        return $rid;
    }

    /**
     * 计算概率
     * @param $proArr
     * @return int|string
     */
    private function get_rand($proArr)
    {
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