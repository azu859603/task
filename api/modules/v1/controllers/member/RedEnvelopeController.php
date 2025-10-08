<?php

namespace api\modules\v1\controllers\member;

use api\controllers\OnAuthController;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use common\models\member\RedEnvelope;
use Yii;
use yii\web\UnprocessableEntityHttpException;

class RedEnvelopeController extends OnAuthController
{

    public $modelClass = RedEnvelope::class;

    /**
     * 领取红包
     * @return array|mixed|\yii\db\ActiveRecord
     * @throws UnprocessableEntityHttpException
     */
    public function actionCreate()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        $id = Yii::$app->request->post('id');
        if (empty($id)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '领取失败！');
        }
        // 判断用户当天已签到次数
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $red_envelope_sql = "select * from t_red_envelope where `id` = {$id} and `member_id` = {$this->memberId} and `is_get` = 0 and `type` = 1 for update";
            $red_envelope_lock = Yii::$app->db->createCommand($red_envelope_sql)->queryOne();
            if (empty($red_envelope_lock)) {
                throw new UnprocessableEntityHttpException('该红包已被领取！');
            }
            $red_envelope = RedEnvelope::findOne($id);
            $red_envelope->is_get = 1;
            $red_envelope->save();
            if ($red_envelope->money > 0) {
                Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                    'member' => Member::findOne($this->memberId),
                    'pay_type' => CreditsLog::GIFT_TYPE,
                    'num' => $red_envelope->money,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => "【红包】系统赠送获得" . $red_envelope->title,
                ]));
            }
            $transaction->commit();
            return ResultHelper::json(ResultHelper::SUCCESS_CODE, "领取成功！");
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ResultHelper::json(ResultHelper::ERROR_CODE, $e->getMessage());
        }
    }

    /**
     * 读取升级弹窗
     * @return void
     */
    public function actionRead()
    {
        $id = Yii::$app->request->post('id');
        if (empty($model = RedEnvelope::find()->where(['id' => $id, 'member_id' => $this->memberId, 'type' => 2])->one())) {
            return ResultHelper::json(ResultHelper::SUCCESS_CODE, "系统繁忙,请稍后再试");
        }
        $model->is_get = 1;
        $model->save();
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "OK");
    }


    /**
     *
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
        if (in_array($action, ['index', 'view', 'update', 'delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}