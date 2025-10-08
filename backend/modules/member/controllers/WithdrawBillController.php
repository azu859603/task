<?php

namespace backend\modules\member\controllers;

use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\member\Member;
use Yii;
use common\models\member\WithdrawBill;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;

/**
 * WithdrawBill
 *
 * Class WithdrawBillController
 * @package backend\modules\member\controllers
 */
class WithdrawBillController extends BaseController
{
    use Curd;

    /**
     * @var WithdrawBill
     */
    public $modelClass = WithdrawBill::class;


    /**
     * 首页
     * @return array|string
     * @throws \yii\web\NotFoundHttpException
     * @author 原创脉冲
     */
    public function actionIndex()
    {
        if (Yii::$app->request->post('hasEditable')) {
            $id = Yii::$app->request->post('editableKey');//获取ID
            Yii::$app->response->format = yii\web\Response::FORMAT_JSON;
            $model = WithdrawBill::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('WithdrawBill'));
            $post = ['WithdrawBill' => $posted];
            if ($model->load($post) && $model->save(false)) {
                $output = $model->$attribute;
                if ($attribute == 'remark') {
                    if (!empty($model->$attribute) && mb_strlen($model->$attribute) > 4) {
                        $output = mb_substr($model->$attribute, 0, 4, 'utf-8') . "..";
                    }
                }
            } else {
                //由于本插件不会自动捕捉model的error，所以需要放在$message中展示出来
                $message = $model->getFirstError($attribute);
            };
            return ['output' => $output, 'message' => $message];
        } else {
            $searchModel = new SearchModel([
                'model' => $this->modelClass,
                'scenario' => 'default',
                'partialMatchAttributes' => [], // 模糊查询
                'defaultOrder' => [
                    'status' => SORT_ASC,
                    'created_at' => SORT_DESC,
                    'id' => SORT_DESC,
                ],
                'pageSize' => $this->pageSize
            ]);

            $dataProvider = $searchModel
                ->search(Yii::$app->request->queryParams);
            $dataProvider->query
                ->with('member');


            $backend_id = Yii::$app->user->identity->getId();
            if ($backend_id != 1) {
                $a_id = Yii::$app->user->identity->aMember->id;
                $childrenIds = Member::getChildrenIds($a_id);
                $dataProvider->query->andFilterWhere(['in', 'member_id', $childrenIds]);
            }

            $sum_withdraw_money = $dataProvider->query->sum('withdraw_money')??0;

            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
                'sum_withdraw_money' => $sum_withdraw_money,
            ]);
        }
    }

    /**
     *  支付导出
     * @param $ids
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionPayExport($ids)
    {
        $ids = explode(',', $ids);
        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => [], // 模糊查询
            'defaultOrder' => [
//                'status' => SORT_ASC,
                'created_at' => SORT_DESC,
                'id' => SORT_DESC,
            ],
            'pageSize' => count($ids)
        ]);

        $dataProvider = $searchModel
            ->search(Yii::$app->request->queryParams);
        $dataProvider->query
            ->where(['in', 'id', $ids])
            ->with(['member', 'account']);
        return $this->render('pay-export', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * 审核提现订单
     * @param $id
     * @param $status
     * @param string $remark
     * @return mixed
     */
    public function actionCheck($id, $status, $remark = "")
    {
        RedisHelper::verify($id, $this->action->id);
        $model = WithdrawBill::find()->where(['id' => $id, 'status' => 0])->one();
        if (empty($model)) {
            return $this->message("该条记录已被操作！", $this->redirect(Yii::$app->request->referrer), 'error');
        }
        $model->status = $status;
        $model->remark = $remark;
        $model->save(false);
        return $this->message("审核成功！", $this->redirect(Yii::$app->request->referrer));
    }

    public function actionNoPass()
    {
        $id = Yii::$app->request->get('id');
        $status = Yii::$app->request->get('status');
        $model = WithdrawBill::findOne($id);
        $model->status = $status;
        if ($model->load(Yii::$app->request->post())) {
            return $this->redirect(['check', 'id' => $id, 'status' => $model->status, 'remark' => $model->remark]);
        }
        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }

    /**
     *  批量操作
     */
    public function actionBatchEdit()
    {
        $ids = Yii::$app->request->post('ids', []);
        if (empty($ids)) {
            return ResultHelper::json(422, '请选择数据进行操作');
        }
        // 先判断所选订单内是否已经被别人处理过
        foreach ($ids as $id) {
            if (empty(WithdrawBill::find()->where(['id' => $id, 'status' => 0])->exists())) {
                return ResultHelper::json(422, '所选操作项包含已审核内容');
            }
        }
        $params = Yii::$app->request->post('params');
        foreach ($ids as $id) {
            $model = WithdrawBill::find()->where(['id' => $id, 'status' => 0])->one();
            $model->status = $params[0];
            $model->remark = $params[1];
            $model->save(false);
        }
        return ResultHelper::json(200, '操作成功');
    }

    /**
     * @return array|mixed|string
     */
    public function actionWithdrawSwitch()
    {
        $value = Yii::$app->debris->config('withdraw_count_switch');
        if ($value == 1) {
            $value = 0;
        } else {
            $value = 1;
        }
        $result = Yii::$app->debris->updateConfig('withdraw_count_switch', $value);
        if ($result == false) {
            return $this->message("操作失败！", $this->redirect(Yii::$app->request->referrer), 'error');
        }
        if ($value == 1) {
            return $this->message("提现消息提示音已开启！", $this->redirect(Yii::$app->request->referrer));
        } else {
            return $this->message("提现消息提示音已关闭！", $this->redirect(Yii::$app->request->referrer), 'error');
        }
    }
}
