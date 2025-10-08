<?php

namespace backend\modules\member\controllers;

use common\helpers\ResultHelper;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use Yii;
use common\models\member\RealnameAudit;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;

/**
 * RealnameAudit
 *
 * Class RealnameAuditController
 * @package backend\modules\member\controllers
 */
class RealnameAuditController extends BaseController
{
    use Curd;

    /**
     * @var RealnameAudit
     */
    public $modelClass = RealnameAudit::class;


    /**
     * 首页
     * @return array|string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionIndex()
    {
        if (Yii::$app->request->post('hasEditable')) {
            $id = Yii::$app->request->post('editableKey');//获取ID
            Yii::$app->response->format = yii\web\Response::FORMAT_JSON;
            $model = RealnameAudit::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('RealnameAudit'));
            $post = ['RealnameAudit' => $posted];
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
            }
            return ['output' => $output, 'message' => $message];
        } else {
            $searchModel = new SearchModel([
                'model' => $this->modelClass,
                'scenario' => 'default',
                'partialMatchAttributes' => [], // 模糊查询
                'defaultOrder' => [
                    'status' => SORT_ASC,
                    'id' => SORT_DESC,
                ],
                'pageSize' => $this->pageSize
            ]);

            $dataProvider = $searchModel
                ->search(Yii::$app->request->queryParams);

            $backend_id = Yii::$app->user->identity->getId();
            if ($backend_id != 1) {
                $a_id = Yii::$app->user->identity->aMember->id;
                $childrenIds = Member::getChildrenIds($a_id);
                $dataProvider->query->andFilterWhere(['in', 'member_id', $childrenIds]);
            }

            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
            ]);
        }
    }

    /**
     * 通过实名认证申请
     * @param $id
     * @return void
     */
    public function actionPass($id)
    {
        $model = RealnameAudit::find()->where(['status' => 0, 'id' => $id])->one();
        if (empty($model)) {
            return $this->message("该条记录已被操作！", $this->redirect(Yii::$app->request->referrer), 'error');
        }
        $model->created_by = Yii::$app->user->identity->getId();
        $model->status = 1;

        $member = Member::findOne($model->member_id);
        $member->realname = $model->realname;
        $member->identification_number = $model->identification_number;
        $member->realname_status = 1;
        if ($member->save()) {
            $model->save();
            // 赠送奖金
            $realname_send_money = Yii::$app->debris->config('realname_send_money');
            if ($realname_send_money > 0) {
                Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                    'member' => $member,
                    'pay_type' => CreditsLog::GIFT_TYPE,
                    'num' => $realname_send_money,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => "【系统】完成实名认证获得奖励",
                ]));
            }
            return $this->message("已通过申请，操作成功！", $this->redirect(Yii::$app->request->referrer));
        } else {
            $error = array_values($member->errors) ? array_values($member->errors) : [['系统繁忙,请稍后再试']];
            return $this->message($error[0][0], $this->redirect(Yii::$app->request->referrer), 'error');
        }
    }

    /**
     * 拒绝实名认证申请
     * @param $id
     * @return mixed|string
     * @throws \yii\base\ExitException
     */
    public function actionReject($id)
    {
        $model = RealnameAudit::find()->where(['status' => 0, 'id' => $id])->one();
        if (empty($model)) {
            return $this->message("该条记录已被操作！", $this->redirect(Yii::$app->request->referrer), 'error');
        }
        $model->status = 2;
        $model->created_by = Yii::$app->user->identity->getId();
        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {

            return $model->save()
                ? $this->message("已拒绝申请，操作成功！", $this->redirect(Yii::$app->request->referrer))
                : $this->message($this->getError($model), $this->redirect(Yii::$app->request->referrer), 'error');
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
            if (empty(RealnameAudit::find()->where(['id' => $id, 'status' => 0])->exists())) {
                return ResultHelper::json(422, '所选操作项包含已审核内容');
            }
        }
        $params = Yii::$app->request->post('params');
        foreach ($ids as $id) {
            $model = RealnameAudit::find()->where(['id' => $id, 'status' => 0])->one();
            $model->status = $params[0];
            $model->created_by = Yii::$app->user->identity->getId();
            $model->save(false);
            if ($params[0] == 1) {
                $member = Member::findOne($model->member_id);
                $member->realname = $model->realname;
                $member->identification_number = $model->identification_number;
                $member->realname_status = 1;
                $member->save();
            }
        }
        return ResultHelper::json(200, '操作成功');
    }

    /**
     * @return array|mixed|string
     */
    public function actionRealnameSwitch()
    {
        $value = Yii::$app->debris->config('realname_count_switch');
        if ($value == 1) {
            $value = 0;
        } else {
            $value = 1;
        }
        $result = Yii::$app->debris->updateConfig('realname_count_switch', $value);
        if ($result == false) {
            return $this->message("操作失败！", $this->redirect(Yii::$app->request->referrer), 'error');
        }
        if ($value == 1) {
            return $this->message("实名审核消息提示音已开启！", $this->redirect(Yii::$app->request->referrer));
        } else {
            return $this->message("实名审核消息提示音已关闭！", $this->redirect(Yii::$app->request->referrer), 'error');
        }
    }
}
