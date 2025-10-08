<?php

namespace api\modules\v1\controllers\member;

use api\controllers\OnAuthController;
use common\helpers\CommonPluginHelper;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\common\Statistics;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use common\models\member\MemberCard;
use yii\data\ActiveDataProvider;
use Yii;

class CardController extends OnAuthController
{
    public $modelClass = MemberCard::class;

    /**
     * 银行卡列表
     * @return array|ActiveDataProvider|\yii\db\ActiveRecord[]
     */
    public function actionIndex()
    {
        return MemberCard::find()
            ->where(['member_id' => $this->memberId])
            ->orderBy('id desc')
            ->asArray()
            ->all();
    }

    /**
     * 创建
     *
     * @return mixed|\yii\db\ActiveRecord
     */
    public function actionCreate()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
//        $member = Member::findOne($this->memberId);
//        if (empty($member['realname'])) {
//            return ResultHelper::json(ResultHelper::ERROR_CODE, '请您先实名认证后再继续操作');
//        }
//        $url = "https://lundroid.market.alicloudapi.com/lianzhuo/verifi";
//        $data = [
//            'acct_name' => $member['realname'],
//            'acct_pan' => trim(Yii::$app->request->post('bank_card')),
//        ];
//        $data = http_build_query($data);
//        $url = $url . "?" . $data;
//        $headers = array();
//        $appcode = Yii::$app->debris->config('app_code');
//        array_push($headers, "Authorization:APPCODE " . $appcode);
//        $result = json_decode(CommonPluginHelper::curl_get($url, $headers), true);
//        if (empty($result)) {
//            return ResultHelper::json(ResultHelper::ERROR_CODE, "绑定银行卡失败,请稍后再试");
//        }
//        if ($result['resp']['code'] != 0) {
//            return ResultHelper::json(ResultHelper::ERROR_CODE, "请输入实名认证用户下的真实银行卡号码");
//        }

        /* @var $model \yii\db\ActiveRecord */
        $model = new $this->modelClass();
        $model->attributes = Yii::$app->request->post();
        $model->member_id = Yii::$app->user->identity->member_id;
        if (!$model->save()) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, $this->getError($model));
        }
        // 推荐人红包
//        $recommend_invitation_code_money = Yii::$app->debris->config('recommend_invitation_code_money');

        // 判断是否返佣
//        if ($recommend_invitation_code_money > 0 && $member->return_recommend == 0 && !empty($member->pid)) {
//            $remark = "推荐用户成功，获得" . $recommend_invitation_code_money . "元奖金";
//            Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
//                'member' => Member::findOne($member->pid),
//                'pay_type' => CreditsLog::COMMISSION_TYPE,
//                'num' => $recommend_invitation_code_money,
//                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
//                'remark' => "【返佣】" . $remark,
//            ]));
//            // 加入佣金统计
//            Statistics::updateCommissionMoney(date('Y-m-d'), $recommend_invitation_code_money);
//            $member->return_recommend = 1;
//            $member->save(false);
//        }
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "绑定成功");
    }

    /**
     * 解绑
     * @return array|mixed
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionUnbind()
    {
        $id = Yii::$app->request->post('id');
        if (empty($card = MemberCard::find()->where(['id' => $id, 'member_id' => $this->memberId])->one())) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "该银行卡不存在");
        }
        $memberInfo = Member::find()->where(['id' => $this->memberId])->one();
        // 验证安全密码
        $safety_password = Yii::$app->request->post('safety_password');
        if (empty($safety_password)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "安全密码不能为空");
        }
        $reslut = $memberInfo->validateSafetyPassword($safety_password);
        if (!$reslut) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "安全密码错误");
        }
        $card->delete();
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "解绑成功");
    }

    /**
     * 权限验证
     *
     * @param string $action 当前的方法
     * @param null $model 当前的模型类
     * @param array $params $_GET变量
     * @throws \yii\web\BadRequestHttpException
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        // 方法名称
        if (in_array($action, ['view', 'update', 'delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}