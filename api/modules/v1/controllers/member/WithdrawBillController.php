<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2020/7/8
 * Time: 2:44
 */

namespace api\modules\v1\controllers\member;


use api\controllers\OnAuthController;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\member\Member;
use common\models\member\MemberCard;
use common\models\member\WithdrawBill;
use common\models\tea\InvestmentBill;
use GatewayClient\Gateway;
use yii\data\ActiveDataProvider;
use Yii;
use yii\helpers\Json;

class WithdrawBillController extends OnAuthController
{
    public $modelClass = WithdrawBill::class;

    /**
     * 提现订单列表
     * @return ActiveDataProvider
     */
    public function actionIndex()
    {
        $status = Yii::$app->request->get('status', 1);
        return new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->select([
                    'id',
                    'sn',
                    'withdraw_money',
                    'real_withdraw_money',
                    'handling_fees',
                    'type',
                    'FROM_UNIXTIME(`created_at`,\'%Y-%m-%d %H:%i:%s\') as created_at',
                    'FROM_UNIXTIME(`updated_at`,\'%Y-%m-%d %H:%i:%s\') as updated_at',
                    'status',
                    'remark'
                ])
                ->where(['member_id' => $this->memberId, 'status' => $status])
                ->orderBy('created_at desc')
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
    }

    /**
     * 创建提现订单
     * @return array|mixed|\yii\db\ActiveRecord
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionCreate()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        if (!Yii::$app->debris->config('withdraw_switch')) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "提现功能暂未开启");
        }
        $minimum_withdraw_amount = Yii::$app->debris->config('minimum_withdraw_amount');
        if (Yii::$app->request->post('withdraw_money') < $minimum_withdraw_amount) {
//            return ResultHelper::json(ResultHelper::ERROR_CODE, '最低提现金额为' . $minimum_withdraw_amount . '元');
            return ResultHelper::json(ResultHelper::ERROR_CODE, '该金额无法进行提现');
        }
        // 判断是否实名制
        $memberInfo = Member::find()->where(['id' => $this->memberId])->one();
        if (empty($memberInfo->realname)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '请您先实名认证后再继续操作');
        }
        // 判断是否能提现
        if ($memberInfo->withdraw_switch != 1) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '提现功能暂未开启');
        }
        // 验证安全密码
        $safety_password = Yii::$app->request->post('safety_password');
        if (empty($safety_password)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "安全密码不能为空");
        }
        $reslut = $memberInfo->validateSafetyPassword($safety_password);
        if (!$reslut) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "安全密码错误");
        }


        // 判断是否投资过
//        $investment_can_withdraw_switch = Yii::$app->debris->config('investment_can_withdraw_switch');
//        if (!InvestmentBill::find()->where(['member_id' => $this->memberId])->exists() && $investment_can_withdraw_switch == 1) {
//            return ResultHelper::json(ResultHelper::ERROR_CODE, "需要参与一次项目投资才能进行提现操作！");
//        }

        // 判断提现时间段
        $now_time = time();
        $withdraw_time_start = Yii::$app->debris->config('withdraw_time_start');
        $withdraw_time_end = Yii::$app->debris->config('withdraw_time_end');
        if ($now_time < strtotime($withdraw_time_start) || $now_time > strtotime($withdraw_time_end)) {
//            return ResultHelper::json(ResultHelper::ERROR_CODE, '请您在每天' . $withdraw_time_start . '到' . $withdraw_time_end . '时间段内申请提现');
            return ResultHelper::json(ResultHelper::ERROR_CODE, '当前时间段无法进行提现');
        }

        // 验证银行卡列表
        $card_id = Yii::$app->request->post('card_id');
        $type = Yii::$app->request->post('type');
        if ($type == 5) {
            if (empty($card_id)) {
                return ResultHelper::json(ResultHelper::ERROR_CODE, "请选择银行卡");
            }
            if (!MemberCard::find()->where(['id' => $card_id, 'member_id' => $this->memberId])->exists()) {
                return ResultHelper::json(ResultHelper::ERROR_CODE, "银行卡错误，请联系客服核查");
            }
        }
        $model = new WithdrawBill();
        if ($model->load(Yii::$app->request->post(), '') && $model->validate() && $model->save(false)) {
            return ResultHelper::json(ResultHelper::SUCCESS_CODE, "提交成功", ['sn' => $model->sn, 'created_at' => $model->created_at,'handling_fees'=>$model->handling_fees]);
        } else {
            $error = array_values($model->errors) ? array_values($model->errors) : [['系统繁忙,请稍后再试']];
            return ResultHelper::json(ResultHelper::ERROR_CODE, $error[0][0]);
        }
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