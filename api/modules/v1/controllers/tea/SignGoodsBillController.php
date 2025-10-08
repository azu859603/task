<?php


namespace api\modules\v1\controllers\tea;

use common\models\forms\CreditsLogForm;
use api\controllers\OnAuthController;
use common\enums\StatusEnum;
use common\helpers\CommonPluginHelper;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\member\Account;
use common\models\member\CreditsLog;
use common\models\member\Member;
use common\models\tea\SignGoodsBill;
use common\models\tea\SignGoodsList;
use yii\data\ActiveDataProvider;
use Yii;
use yii\web\UnprocessableEntityHttpException;

class SignGoodsBillController extends OnAuthController
{

    public $modelClass = SignGoodsBill::class;

    public function actionIndex()
    {
        $status = Yii::$app->request->get('status');
        if (!in_array($status, array_keys(SignGoodsBill::$statusArray))) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "类型不正确!");
        }
        return new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->select([
                    'id',
                    'sn',
                    'g_id',
                    'remark',
                    'status',
                    'FROM_UNIXTIME(`created_at`,\'%Y-%m-%d %H:%i:%s\') as created_at', // 发布时间
                ])
                ->where(['member_id' => $this->memberId, 'status' => $status])
                ->with(['list' => function ($query) {
                    $query->select(['id', 'title']);
                }])
                ->orderBy(['created_at' => SORT_DESC])
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
    }

    /**
     * @return array|mixed|\yii\db\ActiveRecord
     * @throws UnprocessableEntityHttpException
     */
    public function actionCreate()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        $id = Yii::$app->request->post('id');
        // 判断商品数量
        $goods = SignGoodsList::find()->where(['id' => $id, 'status' => StatusEnum::ENABLED])->one();
        if (empty($goods)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "该商品暂时无法兑换!");
        }

        $member_remark = Yii::$app->request->post('member_remark');
        $get_username = Yii::$app->request->post('get_username');
        $get_mobile = Yii::$app->request->post('get_mobile');
        if (empty($member_remark) && $goods->type == 1) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "请输入收货信息!");
        }

        if (empty($goods->money)) {
            if ($goods->type == 2 || $goods->type == 3) {
                return ResultHelper::json(ResultHelper::ERROR_CODE, "兑换失败，请联系在线客服!");
            }
        }

        if ($goods->remaining_amount <= 0) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "该商品已被兑换完,请兑换其他商品!");
        }
        // 下单
        // 开启事务同步更新
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $account_sql = "select * from rf_member_account where `member_id`={$this->memberId} for update";
            $account_lock = Yii::$app->db->createCommand($account_sql)->queryOne();
            if ($goods->sign_day > $account_lock['user_integral']) {
                throw new UnprocessableEntityHttpException('积分不足,签到可获得积分！');
            }
            if ($goods->experience > $account_lock['experience']) {
                throw new UnprocessableEntityHttpException('经验不足,当前礼品兑换需要' . $goods->experience . '点经验值！');
            }
            // 先查询该商品已兑换次数
            $count = CreditsLog::find()->where(['map_id' => $id, 'member_id' => $this->memberId, 'pay_type' => CreditsLog::EXCHANGE_TYPE])->count();
            if ($count >= $goods->times) {
                throw new UnprocessableEntityHttpException('该商品只能兑换' . $goods->times . '次,您已完成兑换！');
            }
            // 扣除积分
            Yii::$app->services->memberCreditsLog->decrInt(new CreditsLogForm([
                'member' => Member::findOne($this->memberId),
                'pay_type' => CreditsLog::EXCHANGE_TYPE,
                'num' => $goods->sign_day,
                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                'remark' => "【兑换】积分兑换商品",
                'map_id' => $goods->id,
            ]));
            if ($goods->type == 1) { // 实物兑换
                $model = new SignGoodsBill();
                $model->member_id = $this->memberId;
                $model->sn = CommonPluginHelper::getSn($this->memberId);
                $model->g_id = $id;
                $model->member_remark = $member_remark;
                $model->get_username = $get_username;
                $model->get_mobile = $get_mobile;
                $model->save();
            } elseif ($goods->type == 2) {// 现金红包
                Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                    'member' => Member::findOne($this->memberId),
                    'num' => $goods->money,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => '【兑换】积分兑换红包成功，获得' . $goods->money . '元',
                    'pay_type' => CreditsLog::EXCHANGE_TYPE
                ]));
            } else { // 幸运抽奖
                $member = Member::findOne($this->memberId);
                $member->free_lottery_number += $goods->money;
                $member->save(false);
            }
            $goods->remaining_amount -= 1;
            $goods->save(false);
            $transaction->commit();
            return ResultHelper::json(ResultHelper::SUCCESS_CODE, "兑换成功！");
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ResultHelper::json(ResultHelper::ERROR_CODE, $e->getMessage());
        }
    }

    public function actionOver()
    {
        $id = Yii::$app->request->post('id');
        $model = SignGoodsBill::find()->where(['id' => $id, 'member_id' => $this->memberId, 'status' => 2])->one();
        if (empty($model)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "该订单无法确认收货！");
        }
        $model->status = 3;
        $model->over_time = time();
        if ($model->save()) {
            return ResultHelper::json(ResultHelper::SUCCESS_CODE, "确认收货成功！");
        } else {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "确认收货失败,请联系客服处理！");
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