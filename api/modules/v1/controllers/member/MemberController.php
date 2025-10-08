<?php

namespace api\modules\v1\controllers\member;

use api\modules\v1\forms\member\IntegralExchangeForm;
use api\modules\v1\forms\member\UpdateMemberForm;
use api\modules\v1\forms\member\UpdatePasswordForm;
use api\modules\v1\forms\member\UpdateSafetyPasswordForm;
use common\helpers\ArrayHelper;
use common\helpers\BcHelper;
use common\helpers\CommonPluginHelper;
use common\helpers\DateHelper;
use common\helpers\GatewayInit;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\common\Languages;
use common\models\dj\Orders;
use common\models\dj\PromotionOrder;
use common\models\dj\SellerAvailableOrder;
use common\models\forms\CreditsLogForm;
use common\models\member\Account;
use common\models\member\CreditsLog;
use GatewayClient\Gateway;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use api\controllers\OnAuthController;
use common\enums\StatusEnum;
use common\models\member\Member;
use Yii;

/**
 * 会员接口
 *
 * Class MemberController
 * @package api\modules\v1\controllers\member
 * @property \yii\db\ActiveRecord $modelClass
 * @author 原创脉冲
 */
class MemberController extends OnAuthController
{
    /**
     * @var Member
     */
    public $modelClass = Member::class;


    /**
     * 个人中心
     * @return array|null|\yii\data\ActiveDataProvider|\yii\db\ActiveRecord
     */
    public function actionIndex()
    {
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        $member = Member::getMemberInfoByMemberId($this->memberId, $lang);
        // 今日数据
        $today = DateHelper::today();
        $member['today_money'] = Orders::find()
                ->where(['seller_id' => $this->memberId, 'status' => 1])
                ->andWhere(['between', 'created_at', $today['start'], $today['end']])
                ->sum('money') ?? "0.00";
        // 7日数据
        $time = time();
        $member['day7_money'] = Orders::find()
                ->where(['seller_id' => $this->memberId, 'status' => 1])
                ->andWhere(['between', 'created_at', $time - 7 * 86400, $time])
                ->sum('money') ?? "0.00";
        // 30日数据
        $member['day30_money'] = Orders::find()
                ->where(['seller_id' => $this->memberId, 'status' => 1])
                ->andWhere(['between', 'created_at', $time - 30 * 86400, $time])
                ->sum('money') ?? "0.00";
        // 预计利润
        $member['can_get_income'] = Orders::find()
                ->where(['seller_id' => $this->memberId, 'status' => 1, 'income_status' => 0])
                ->sum('income') ?? "0.00";
        // 待发货
        $member['waiting_number'] = Orders::find()
                ->where(['seller_id' => $this->memberId, 'status' => 0])
                ->andWhere(['<', 'created_at', time()])
                ->count() ?? 0;
        // 总订单数
        $member['all_number'] = Orders::find()
                ->where(['seller_id' => $this->memberId])
                ->andWhere(['<', 'created_at', time()])
                ->count() ?? 0;
        // 5日销量
        $dat5_money_array['date5_time'][] = date("m-d", strtotime("-4 day"));
        $dat5_money_array['date5_time'][] = date("m-d", strtotime("-3 day"));
        $dat5_money_array['date5_time'][] = date("m-d", strtotime("-2 day"));
        $dat5_money_array['date5_time'][] = date("m-d", strtotime("-1 day"));
        $dat5_money_array['date5_time'][] = date("m-d");
        $dat5_money_array['date5_number'][] = Orders::find()
                ->where(['seller_id' => $this->memberId, 'status' => 1])
                ->andWhere(['between', 'created_at', $today['start'] - 86400 * 4, $today['start'] - 86400 * 3])
                ->sum('money') ?? "4.00";
        $dat5_money_array['date5_number'][] = Orders::find()
                ->where(['seller_id' => $this->memberId, 'status' => 1])
                ->andWhere(['between', 'created_at', $today['start'] - 86400 * 3, $today['start'] - 86400 * 2])
                ->sum('money') ?? "3.00";
        $dat5_money_array['date5_number'][] = Orders::find()
                ->where(['seller_id' => $this->memberId, 'status' => 1])
                ->andWhere(['between', 'created_at', $today['start'] - 86400 * 2, $today['start'] - 86400])
                ->sum('money') ?? "2.00";
        $dat5_money_array['date5_number'][] = Orders::find()
                ->where(['seller_id' => $this->memberId, 'status' => 1])
                ->andWhere(['between', 'created_at', $today['start'] - 86400, $today['start']])
                ->sum('money') ?? "1.00";
        $dat5_money_array['date5_number'][] = $member['today_money'];
        $member['day5_money_array'] = $dat5_money_array;

        // 预售订单统计 预售金额 销售金额
        $ys_money = SellerAvailableOrder::find()
                ->where(['member_id' => $this->memberId])
//                ->andWhere(['>', 'buy_number', 0])
                ->sum('money') ?? "0.00";

        $xs_money = Orders::find()
                ->where(['seller_id' => $this->memberId, 'income_status' => 0])
                ->andWhere(['<', 'created_at', time()])
                ->sum('money') ?? "0.00";
        $member['ys_money'] = $ys_money;
        $member['xs_money'] = $xs_money;

        return $member;
    }


    /**
     * 绑定client_id
     * @return mixed
     * @author 原创脉冲
     */
//    public function actionBinding()
//    {
//        if (empty($client_id = Yii::$app->request->post('client_id'))) {
//            return ResultHelper::json(ResultHelper::ERROR_CODE, '未成功获取到clientID');
//        }
//        GatewayInit::initBase();
//        Gateway::bindUid($client_id, $this->memberId);
//        Member::updateAll(['online_status' => 1], ['id' => $this->memberId]);
//        return ResultHelper::json(ResultHelper::SUCCESS_CODE, 'OK');
//    }


    /**
     * 邀请列表
     */
    public function actionList()
    {
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        return new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->select([
                    'id',
                    'nickname',
                    'realname',
                    'mobile',
                    'head_portrait',
                    'current_level',
                    'FROM_UNIXTIME(`created_at`,\'%Y-%m-%d\') as created_at',
                ])
                ->where(['pid' => $this->memberId])
                ->with([
                    'sellerLevel' => function ($query) use ($lang) {
                        $query->select(['id', 'level', 'number'])
                            ->with(['translation' => function ($query) use ($lang) {
                                $query->where(['lang' => $lang]);
                            }]);
                    },
                    'account' => function ($query) {
                        $query->select(['id', 'member_id', 'recommend_number', 'investment_income']);
                    }
                ])
                ->orderBy('created_at desc,id desc')
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
    }

    public function actionTeam()
    {
        $data = [];
        $account = Account::find()->where(['member_id' => $this->memberId])->select(['recommend_number', 'recommend_money'])->asArray()->one();
        $data['recommend_number'] = $account['recommend_number'];
        $data['recommend_money'] = $account['recommend_money'];
        $today = DateHelper::today();
        $data['today_add'] = Member::find()
                ->where(['pid' => $this->memberId, 'type' => 1])
                ->andWhere(['between', 'created_at', $today['start'], $today['end']])
                ->count() ?? 0;
        $data['active_member'] = Member::find()
                ->where(['pid' => $this->memberId, 'type' => 1])
                ->andWhere(['>', 'recharge_money', 0])
                ->count() ?? 0;
        return $data;
    }


    /**
     * 修改密码
     * @return mixed
     * @throws \yii\base\Exception
     */
    public function actionUpdatePassword()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        $form = new UpdatePasswordForm();
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, $this->getError($form));
        }
        $model = $form->getMember();
        $model->password_hash = $form->new_password;
//        $model->setPassword($form->new_password);
        $model->save();
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "修改成功");
    }


    /**
     * 验证安全密码
     * @return mixed
     * @throws \yii\base\Exception
     */
    public function actionVerifySafetyPassword()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        $old_safety_password = Yii::$app->request->post('old_safety_password');
        if (empty($old_safety_password)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "旧安全密码不能为空");
        }
        $member = Member::findOne($this->memberId);
        if (empty($member) || !$member->validateSafetyPassword($old_safety_password)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "旧安全密码错误");
        }
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "OK");
    }


    /**
     * 修改安全密码
     * @return mixed
     * @throws \yii\base\Exception
     */
    public function actionUpdateSafetyPassword()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        $form = new UpdateSafetyPasswordForm();
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, $this->getError($form));
        }

        $model = $form->getMember();
        $model->setSafetyPassword($form->new_safety_password);
        $model->save();
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "修改成功");
    }

    /**
     * 修改个人资料
     * @return array|mixed
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionUpdateMember()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        $form = new UpdateMemberForm();
        $scenario_type = Yii::$app->request->post('scenario_type');
        if (empty($scenario_type) || !in_array($scenario_type, $form->scenario_type_array)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "类型不正确");
        }
        $form->scenario = $scenario_type;
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, $this->getError($form));
        }
        $model = Member::findOne($this->memberId);
        $member = CommonPluginHelper::notNullArray(ArrayHelper::toArray($form));
        // 对接实名认证
        if ($scenario_type == "realname") {
            if ($model->realname != "") {
                return ResultHelper::json(ResultHelper::ERROR_CODE, "您已完成实名认证审核，请勿重复提交！");
            }
            $url = "https://idcard.market.alicloudapi.com/lianzhuo/idcard";
            $data = [
                'cardno' => trim(Yii::$app->request->post('identification_number')),
                'name' => trim(Yii::$app->request->post('realname')),
            ];
            $data = http_build_query($data);
            $url = $url . "?" . $data;
            $headers = array();
            $appcode = Yii::$app->debris->config('app_code');
            array_push($headers, "Authorization:APPCODE " . $appcode);
            $result = json_decode(CommonPluginHelper::curl_get($url, $headers), true);
            if (empty($result)) {
                return ResultHelper::json(ResultHelper::ERROR_CODE, "实名认证失败,请稍后再试");
            }
            if ($result['resp']['code'] != 0) {
                return ResultHelper::json(ResultHelper::ERROR_CODE, "请输入真实的姓名和身份证号码");
            }
            $member['realname_status'] = 1;
            // 赠送奖金
            $realname_send_money = Yii::$app->debris->config('realname_send_money');
            if ($realname_send_money > 0) {
                Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                    'member' => $model,
                    'pay_type' => CreditsLog::GIFT_TYPE,
                    'num' => $realname_send_money,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => "【系统】完成实名认证获得奖金",
                ]));
            }
        }

        // 更改推流状态
        if ($scenario_type == "push_flow_switch") {
            if ($form->push_flow_switch == 1) {
                $promotionOrder = new PromotionOrder();
                $promotionOrder->title = date("Ymd");
                $promotionOrder->member_id = $this->memberId;
                $promotionOrder->type = 1;
                $promotionOrder->status = 1;
                $promotionOrder->money = 0;
                $promotionOrder->number = 0;
                $promotionOrder->all_number = 0;
                $promotionOrder->save(false);
            } else {
                PromotionOrder::updateAll(['status' => 0], ['member_id' => $this->memberId, 'status' => 1]);
            }
        }

        $model->attributes = $member;
        if (!$model->save()) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, $this->getError($model));
        }
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "操作成功");
    }

    /**
     * 积分兑换红包
     * @return array|mixed
     * @throws \yii\web\UnprocessableEntityHttpException
     */
//    public function actionExchange()
//    {
//        $exchange_rate = Yii::$app->debris->config('exchange_rate');
//        if (empty($exchange_rate)) {
//            ResultHelper::json(ResultHelper::ERROR_CODE, "兑换红包功能暂未开放！");
//        }
//        $form = new IntegralExchangeForm();
//        $form->attributes = Yii::$app->request->post();
//        if (!$form->validate()) {
//            return ResultHelper::json(ResultHelper::ERROR_CODE, $this->getError($form));
//        }
//        $member = Member::findOne($this->memberId);
//        Yii::$app->services->memberCreditsLog->decrInt(new CreditsLogForm([
//            'member' => $member,
//            'num' => $form->integral,
//            'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
//            'remark' => "【兑换】消耗积分兑换红包",
//            'pay_type' => CreditsLog::EXCHANGE_TYPE,
//        ]));
//        // 计算兑换金额
//        $money = BcHelper::mul(BcHelper::div($exchange_rate, 100, 4), $form->integral);
//        Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
//            'member' => $member,
//            'num' => $money,
//            'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
//            'remark' => '【兑换】积分兑换红包成功，获得' . $money . '元',
//            'pay_type' => CreditsLog::EXCHANGE_TYPE
//        ]));
//        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "兑换成功！");
//    }

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
        if (in_array($action, ['view', 'update', 'create', 'delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}
