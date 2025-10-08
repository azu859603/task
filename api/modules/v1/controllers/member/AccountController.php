<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2020/7/9
 * Time: 0:29
 */

namespace api\modules\v1\controllers\member;


use api\controllers\OnAuthController;
use api\modules\v1\forms\member\AccountForm;
use common\helpers\ArrayHelper;
use common\helpers\CommonPluginHelper;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\member\Account;
use common\models\member\Member;
use Yii;

class AccountController extends OnAuthController
{
    public $modelClass = Account::class;


    /**
     * 绑定账户
     */
    public function actionCreate()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        $form = new AccountForm();
        $scenario_type = Yii::$app->request->post('scenario_type');
        if (empty($scenario_type) || !in_array($scenario_type, $form::$scenario_type_array)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "类型不正确");
        }
        $form->scenario = $scenario_type;
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, $this->getError($form));
        }
        // 绑定银行卡实名认证判断
        if ($scenario_type == "bank_card") {
            $member = Member::find()->where(['id' => $this->memberId])->select(['realname'])->asArray()->one();
            if (empty($member)) {
                return ResultHelper::json(ResultHelper::ERROR_CODE, '请您先实名认证后再继续操作');
            }
            $url = "https://lundroid.market.alicloudapi.com/lianzhuo/verifi";
            $data = [
                'acct_name' => $member['realname'],
                'acct_pan' => trim(Yii::$app->request->post('bank_card')),
            ];
            $data = http_build_query($data);
            $url = $url . "?" . $data;
            $headers = array();
            $appcode = Yii::$app->debris->config('app_code');
            array_push($headers, "Authorization:APPCODE " . $appcode);
            $result = json_decode(CommonPluginHelper::curl_get($url, $headers), true);
            if (empty($result)) {
                return ResultHelper::json(ResultHelper::ERROR_CODE, "绑定银行卡失败,请稍后再试");
            }
            if ($result['resp']['code'] != 0) {
                return ResultHelper::json(ResultHelper::ERROR_CODE, "请输入实名认证用户下的真实银行卡号码");
            }
        }
        $model = Account::findOne(['member_id' => $this->memberId]);
        $binding_account = CommonPluginHelper::notNullArray(ArrayHelper::toArray($form));
        $model->attributes = $binding_account;
        if (!$model->save(false)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, $this->getError($model));
        }
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "OK");
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
        if (in_array($action, ['index', 'view', 'update', 'delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}