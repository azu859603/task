<?php

namespace backend\modules\base\controllers;

use common\helpers\GatewayInit;
use common\helpers\GoogleAuthenticatorHelper;
use common\models\member\RealnameAudit;
use common\models\member\RechargeBill;
use common\models\member\WithdrawBill;
use common\models\task\Project;
use Yii;
use common\enums\StatusEnum;
use common\models\base\SearchModel;
use common\traits\Curd;
use common\models\backend\Member;
use common\enums\AppEnum;
use common\helpers\ResultHelper;
use backend\controllers\BaseController;
use backend\modules\base\forms\PasswdForm;
use backend\modules\base\forms\MemberForm;
use GatewayClient\Gateway;

/**
 * Class MemberController
 * @package backend\modules\base\controllers
 * @author 原创脉冲
 */
class MemberController extends BaseController
{
    use Curd;

    /**
     * @var Member
     */
    public $modelClass = Member::class;

    /**
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionIndex()
    {
        // 获取当前用户权限的下面的所有用户id，除超级管理员
        $ids = Yii::$app->services->rbacAuthAssignment->getChildIds(AppEnum::BACKEND);

        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => ['username', 'mobile', 'realname'], // 模糊查询
            'defaultOrder' => [
                'type' => SORT_DESC,
                'id' => SORT_DESC,
            ],
            'pageSize' => $this->pageSize,
        ]);

        $dataProvider = $searchModel
            ->search(Yii::$app->request->queryParams);
        $dataProvider->query
            ->andFilterWhere(['in', 'id', $ids])
            ->andWhere(['>=', 'status', StatusEnum::DISABLED])
            ->with('assignment');

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * 编辑/创建
     *
     * @return mixed|string|\yii\web\Response
     * @throws \yii\base\ExitException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function actionAjaxEdit()
    {
        $request = Yii::$app->request;
        $model = new MemberForm();
        $model->id = $request->get('id');
        $model->loadData();
        $model->id != Yii::$app->params['adminAccount'] && $model->scenario = 'generalAdmin';

        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load($request->post())) {
            if ($model->save()) {
                Yii::$app->services->actionLog->create('member/ajax-edit', '管理员修改密码');
                return $this->redirect(['index']);
            } else {
                return $this->message($this->getError($model), $this->redirect(['index']), 'error');
            }
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
            'roles' => Yii::$app->services->rbacAuthRole->getDropDown(AppEnum::BACKEND, true),
        ]);
    }

    /**
     * 个人中心
     *
     * @return mixed|string
     */
    public function actionPersonal()
    {
        $id = Yii::$app->user->id;
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->message('修改个人信息成功', $this->redirect(['personal']));
        }

        return $this->render($this->action->id, [
            'model' => $model,
        ]);
    }

    /**
     * 修改密码
     *
     * @return array|string
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function actionUpPassword()
    {
        $model = new PasswdForm();
        if ($model->load(Yii::$app->request->post())) {
            if (!$model->validate()) {
                return ResultHelper::json(404, $this->getError($model));
            }

            /* @var $member \common\models\backend\Member */
            $member = Yii::$app->user->identity;
            $member->password_hash = Yii::$app->security->generatePasswordHash($model->passwd_new);;

            if ($member->save()) {
                Yii::$app->services->actionLog->create('member/up-password', '修改自身密码');
                Yii::$app->user->logout();

                return ResultHelper::json(200, '修改成功');
            }

            return ResultHelper::json(404, $this->analyErr($member->getFirstErrors()));
        }

        return $this->render($this->action->id, [
            'model' => $model,
        ]);
    }

    /**
     * 绑定client_id
     * @return mixed
     * @author 原创脉冲
     */
    public function actionBinding()
    {
        if (empty($client_id = Yii::$app->request->post('client_id'))) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '未成功获取到clientID');
        }
        if (empty(Yii::$app->user->identity)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '暂未登录');
        }
        GatewayInit::initBase();
        $thisAppEnglishName = Yii::$app->params['thisAppEnglishName'];
        Gateway::joinGroup($client_id, $thisAppEnglishName . '_admin_message');
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, '绑定成功');
    }

    /**
     * 获取未处理充值提现订单数量
     */
    public function actionGetMessage()
    {
        $data = [];
        $data['created_at'] = time();
        if (Yii::$app->user->identity->getId() == 1) {
            $data['recharge_count'] = Yii::$app->debris->config('recharge_count_switch') ? RechargeBill::find()->where(['status' => 0, 'warning_switch' => 1])->count() ?? '0' : '0';
            $data['withdraw_count'] = Yii::$app->debris->config('withdraw_count_switch') ? WithdrawBill::find()->where(['status' => 0])->count() ?? '0' : '0';
            $data['member_count'] = \common\models\member\Member::find()->where(['online_status' => 1])->count() ?? '0';
            $data['realname_count'] = RealnameAudit::find()->where(['status' => 0])->count() ?? '0';
        } else {
            $a_id = Yii::$app->user->identity->aMember->id;
            $childrenIds = \common\models\member\Member::getChildrenIds($a_id);
            $data['recharge_count'] = Yii::$app->debris->config('recharge_count_switch') ? RechargeBill::find()->where(['status' => 0, 'warning_switch' => 1])->andWhere(['in', 'member_id', $childrenIds])->count() ?? '0' : '0';
            $data['withdraw_count'] = Yii::$app->debris->config('withdraw_count_switch') ? WithdrawBill::find()->where(['status' => 0])->andWhere(['in', 'member_id', $childrenIds])->count() ?? '0' : '0';
            $data['member_count'] = \common\models\member\Member::find()->where(['online_status' => 1])->andWhere(['in', 'id', $childrenIds])->count() ?? '0';
            $data['realname_count'] = RealnameAudit::find()->where(['status' => 0])->andWhere(['in', 'id', $childrenIds])->count() ?? '0';
        }
        $model = Project::find()->where(['status' => 1])->andWhere(['<', 'remain_number', 30])->select(['id'])->asArray()->one();
        if (!empty($model)) {
            $data['project'] = $model['id'];
        }

        return ResultHelper::json(ResultHelper::SUCCESS_CODE, '', $data);
    }

    public function actionSetGoogle()
    {
        $id = Yii::$app->user->id;
        $model = $this->findModel($id);
        $web_site_title = Yii::$app->debris->backendConfig('web_site_title');
        if (empty($model->google_secret) || $model->google_switch == 0) {
            $Google = new GoogleAuthenticatorHelper();
            // 生成keys
            $secret = $Google->createSecret();
            $name = $web_site_title . "(" . $model->username . ")";//谷歌验证码里面的标识符
            $model->google_url = $Google->getQRCodeGoogleUrl($name, $secret); //第一个参数是"标识",第二个参数为"安全密匙SecretKey" 生成二维码信息
            $model->google_secret = $secret;
        } else {
            $model->google_url = "https://api.qrserver.com/v1/create-qr-code/?data=otpauth%3A%2F%2Ftotp%2F" . $web_site_title . "%28" . $model->username . "%29%3Fsecret%3D" . $model->google_secret . "&size=200x200&ecc=M";
        }

        if ($model->load(Yii::$app->request->post())) {
            return $model->save()
                ? $this->message("操作成功", $this->redirect(Yii::$app->request->referrer))
                : $this->message($this->getError($model), $this->redirect(Yii::$app->request->referrer), 'error');
        }

        return $this->render($this->action->id, [
            'model' => $model,
        ]);
    }
}