<?php

namespace api\modules\v1\controllers\tea;

use api\controllers\OnAuthController;
use common\enums\StatusEnum;
use common\helpers\BcHelper;
use common\helpers\FileHelper;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\common\Statistics;
use common\models\member\Member;
use common\models\tea\Bill;
use common\models\tea\Project;
use Yii;

class BillController extends OnAuthController
{
    public $modelClass = Bill::class;


    /**
     * 添加订单
     * @return array|mixed|\yii\db\ActiveRecord
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionCreate()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        // 验证ID
        $id = Yii::$app->request->post('id');
        if (empty($id)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '产品信息错误！');
        }
        // 判断项目状态
        if (empty($project = Project::find()->where(['id' => $id, 'status' => StatusEnum::ENABLED])->one())) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '该产品已暂停参与购买，请购买其他产品！');
        }
        // 判断投资金额
        $investment_amount = Yii::$app->request->post('investment_amount');
        if (empty($investment_amount)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '购买金额不能为空！');
        }
        // 判断项目状态
        if (Bill::find()->where(['member_id' => $this->memberId, 'project_id' => $id])->andWhere(['<', 'status', 3])->exists()) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '该项目正在进行中，请结束后再进行购买！');
        }
        $memberInfo = Member::find()
            ->where(['id' => $this->memberId])
            ->with(['account', 'memberLevel'])
            ->one();

        if ($investment_amount > $memberInfo->account->user_money) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '您的余额不足，请前往充值！');
        }
        if ($investment_amount < $project->least_amount) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '起始购买金额为' . $project->least_amount . '元！');
        }
        // 24小时后结算
        $settlementTime = time();
        // 开启事务同步更新
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $model = new Bill();
            // 添加订单
            $model->project_id = $id;
            $model->member_id = $this->memberId;
            $model->investment_amount = $investment_amount;
            $model->settlement_times = $project->deadline;
            $model->created_at = time();
            $model->add_income = $memberInfo->memberLevel->income;
            $model->next_time = $settlementTime + (60 * 60 * 24);
            $model->updated_at = $settlementTime + (($project->deadline) * 60 * 60 * 24);
            $model->save(false);

            // 更新账户信息  用户经验升级
            $memberInfo->account->investment_all_money = BcHelper::add($memberInfo->account->investment_all_money, $investment_amount);
            $memberInfo->account->investment_doing_money = BcHelper::add($memberInfo->account->investment_doing_money, $investment_amount);
            // 获取真实增加经验
            $memberInfo->account->experience = BcHelper::add($memberInfo->account->experience, $investment_amount);
            $memberInfo->account->save(false);
            $memberInfo->investment_status = 1;
            $memberInfo->investment_time = time();
            $memberInfo->save(false);
            // 更新等级
            Yii::$app->services->memberLevel->updateLevel($memberInfo);
            // 加入投资统计
            Statistics::updateInvestment(date('Y-m-d'), date('Y-m-d', $model->updated_at), $investment_amount);
            $transaction->commit();
            return ResultHelper::json(ResultHelper::SUCCESS_CODE, "操作成功");
        } catch (\Exception $e) {
            $transaction->rollBack();
            FileHelper::writeLog($this->getLogPath($this->action->id), $e->getMessage());
            return ResultHelper::json(ResultHelper::ERROR_CODE, "操作失败,请联系客服处理！");
        }
    }

    /**
     * @param $type
     * @return string
     */
    protected function getLogPath($type)
    {
        return Yii::getAlias('@runtime') . "/buy/" . date('Y_m_d') . '/' . $type . '.txt';
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