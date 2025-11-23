<?php

namespace backend\modules\member\controllers;

use backend\modules\member\forms\WithdrawExportForm;
use common\helpers\BcHelper;
use common\helpers\CommonPluginHelper;
use common\helpers\ExcelHelper;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\member\Member;
use common\models\member\MemberCard;
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


//            $backend_id = Yii::$app->user->identity->getId();
//            if ($backend_id != 1) {
//                $a_id = Yii::$app->user->identity->aMember->id;
//                $childrenIds = Member::getChildrenIds($a_id);
//                $dataProvider->query->andFilterWhere(['in', 'member_id', $childrenIds]);
//            }

            $sum_withdraw_money = $dataProvider->query->sum('withdraw_money') ?? 0;

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
    public function actionCheck($id)
    {
        RedisHelper::verify($id, $this->action->id);
        $model = WithdrawBill::find()->where(['id' => $id, 'status' => 0])->one();
        if (empty($model)) {
            return $this->message("该条记录已被操作！", $this->redirect(Yii::$app->request->referrer), 'error');
        }
        $model->status = 1;
        $model->updated_at = time();
        $model->save(false);
        return $this->message("审核成功！", $this->redirect(Yii::$app->request->referrer));
    }

    public function actionNoPass($id)
    {
        $model = WithdrawBill::find()->where(['id' => $id, 'status' => 0])->one();
        if (empty($model)) {
            return $this->message("该条记录已被操作！", $this->redirect(Yii::$app->request->referrer), 'error');
        }
        $model->status = 2;
        if ($model->load(Yii::$app->request->post())) {
            RedisHelper::verify($id, $this->action->id);
            $model->status = 2;
            $model->updated_at = time();
            $model->save(false);
            return $this->message("审核成功！", $this->redirect(Yii::$app->request->referrer));
        }
        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }


    public function actionPayOnBehalf()
    {
        $id = Yii::$app->request->get('id');
        $model = WithdrawBill::find()->where(['id' => $id, 'status' => 0])->with(['card', 'account'])->one();
        if ($model->load(Yii::$app->request->post())) {
            if (empty($model->pay_type)) {
                return $this->message("代付平台必须选择！", $this->redirect(Yii::$app->request->referrer), 'error');
            }
            if ($model->pay_type == 1) {
                $result = self::xfPay($model);
            }
            if (!$result) {
                // 成功代付后
                $model->status = 4;
                $model->save(false);
                return $this->message("操作成功！", $this->redirect(Yii::$app->request->referrer));
            } else {
                return $this->message($result, $this->redirect(Yii::$app->request->referrer), 'error');
            }
        }
        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }

    public static function xfPay($order)
    {
        $post_data = [];
        $post_data['pay_memberid'] = "10052";
        $post_data['pay_orderid'] = $order->sn;
        $post_data['pay_applydate'] = time();
        if ($order->type == 3) {// 支付宝
            $code = 3;
            $pay_name = $order->account->alipay_user_name;
            $bank_name = "支付宝";
            $pay_card = $order->account->alipay_account;
        } elseif ($order->type == 5) {// 银行卡
            $code = 2;
            $pay_name = $order->card->username;
            $bank_name = $order->card->bank_address;
            $pay_card = $order->card->bank_card;
        }
        $post_data['pay_bankcode'] = $code;
        $post_data['pay_amount'] = BcHelper::mul($order->real_withdraw_money, 100, 0);
        $post_data['pay_notifyurl'] = Yii::$app->request->hostInfo . "/notify/xf-pay";
        $post_data['pay_name'] = $pay_name;
        $post_data['pay_card'] = $pay_card;
        $post_data['pay_bankname'] = $bank_name;
        $key = "BE47EFB5CE0A522AF36286BE6FB03B67";
        $post_data['pay_md5sign'] = CommonPluginHelper::xfpay_sign($key, $post_data);
        $pay_url = "https://nova.flaresec.com/order/create/";
        $result_json = CommonPluginHelper::curl_post($pay_url, $post_data);
        $result = json_decode($result_json, true);
        if (!empty($result) && $result['status'] == 1) {
            return false;
        } else {
            return $result['msg'];
        }
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

    public function actionExport()
    {
        // 解除内存限制
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $model = new WithdrawExportForm();
        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            $times = explode("~", $model->created_at);
            $models = WithdrawBill::find()
                ->where(['between', 'created_at', strtotime($times[0]), strtotime($times[1]) + 86400])
                ->with([
                    'member'
                ])
                ->asArray()
                ->all();
            $header = [
                ['ID', 'id'],
                ['账号', 'member.mobile'],
                ['账号', 'sn'],
                ['提现金额', 'withdraw_money'],
                ['汇款金额', 'real_withdraw_money'],
                ['提现类型', 'type', 'selectd', WithdrawBill::$typeExplain],
                ['状态', 'status', 'selectd', WithdrawBill::$statusExplain],
                ['后台备注', 'remark'],
                ['用户端备注', 'user_remark'],
                ['提现时间', 'created_at', 'date', 'Y-m-d H:i:s'],
                ['审核时间', 'updated_at', 'date', 'Y-m-d H:i:s'],
            ];
            return ExcelHelper::exportData($models, $header, '导出提现订单_' . time() . "日期" . $model->created_at);
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }
}
