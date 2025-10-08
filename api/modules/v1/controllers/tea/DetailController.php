<?php

namespace api\modules\v1\controllers\tea;

use api\controllers\OnAuthController;
use common\helpers\DateHelper;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\tea\Bill;
use common\models\tea\Detail;
use Yii;

class DetailController extends OnAuthController
{
    public $modelClass = Detail::class;


    /**
     * 提交凭证
     * @return array|mixed|\yii\db\ActiveRecord
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionCreate()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        // 验证ID
        $id = Yii::$app->request->post('id');
        // 判断项目
        if (empty($bill = Bill::find()->where(['id' => $id, 'member_id' => $this->memberId])->asArray()->one())) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '订单信息错误！');
        }
        $content = Yii::$app->request->post('content');
        if (empty($content)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '凭证不能为空！');
        }
        if (Detail::find()->where(['b_id' => $id, 'member_id' => $this->memberId, 'status' => 0])->exists()) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '打卡审核正在进行中！');
        }
//        $end = $bill['next_time'];
//        $start = $end - 86400;
//        $today = ['start' => $start, 'end' => $end];
//        if (Detail::find()->where(['b_id' => $id, 'member_id' => $this->memberId, 'status' => 1])->andWhere(['between', 'created_at', $today['start'], $today['end']])->exists()) {
//            return ResultHelper::json(ResultHelper::ERROR_CODE, '已完成本轮打卡！');
//        }
        // 添加
        $model = new Detail();
        $model->member_id = $this->memberId;
        $model->b_id = $id;
        $model->project_id = $bill['project_id'];
        $model->content = $content;
        $model->created_at = time();
        $model->save(false);
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "打卡成功，请等待审核！");

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