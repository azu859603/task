<?php

namespace api\modules\v1\controllers;

use common\helpers\CaptchaBuilder;
use common\helpers\CommonPluginHelper;
use common\helpers\GdtHelper;
use common\helpers\RedisHelper;
use common\models\common\Statistics;
use common\models\forms\CreditsLogForm;
use common\models\member\Account;
use common\models\member\CreditsLog;
use common\models\member\MemberLoginLog;
use common\models\tea\GdtAdd;
use common\models\tea\GdtAdd1;
use common\models\tea\GdtToken;
use common\models\tea\RegisterIp;
use Yii;
use yii\helpers\Json;
use yii\web\NotFoundHttpException;
use common\helpers\ResultHelper;
use common\helpers\ArrayHelper;
use common\models\member\Member;
use api\modules\v1\forms\UpPwdForm;
use api\controllers\OnAuthController;
use api\modules\v1\forms\LoginForm;
use api\modules\v1\forms\RefreshForm;
use api\modules\v1\forms\MobileLogin;
use api\modules\v1\forms\SmsCodeForm;
use api\modules\v1\forms\RegisterForm;
use yii\web\UnprocessableEntityHttpException;
use api\modules\v1\forms\UpSafetyPwdForm;

/**
 * 登录接口
 *
 * Class SiteController
 * @package api\modules\v1\controllers
 * @author 原创脉冲
 */
class SiteController extends OnAuthController
{
    public $modelClass = '';

    /**
     * 不用进行登录验证的方法
     *
     * 例如： ['index', 'update', 'create', 'view', 'delete']
     * 默认全部需要验证
     *
     * @var array
     */
    protected $authOptional = ['login', 'refresh', 'mobile-login', 'sms-code', 'register', 'up-pwd', 'captcha', 'register-anonymous'];

    /**
     * 注册
     * @return array|mixed
     * @throws UnprocessableEntityHttpException
     * @throws \yii\base\Exception
     */
    public function actionRegister()
    {
        $register_ip = Yii::$app->request->getUserIP();
//        $redis = RedisHelper::verify($register_ip, $this->action->id);
        // 判断注册功能是否开放
        if (!Yii::$app->debris->config('register_switch')) {
            throw new UnprocessableEntityHttpException('注册功能暂未开放');
        }

        // 判断同一IP注册数
        $register_ip_max = intval(Yii::$app->debris->config('register_ip_max'));
        if ($register_ip_max > 0) {
            // 拿取当前用户IP
            // 根据当前ip查询用户表IP数量
            $user_ip_register_count = Member::find()->where(['register_ip' => $register_ip])->count();
            if ($user_ip_register_count >= $register_ip_max) {
                return ResultHelper::json(422, "当前IP注册量已达到最大值");
            }
        }

        $model = new RegisterForm();
        $model->attributes = Yii::$app->request->post();
        if (!$model->validate()) {
            return ResultHelper::json(422, $this->getError($model));
        }
        $parent = $model->getParent();
        $member = new Member();
        $member->attributes = ArrayHelper::toArray($model);
        $member->promo_code = '';
        $member->merchant_id = !empty($this->getMerchantId()) ? $this->getMerchantId() : 0;
//        $member->password_hash = Yii::$app->security->generatePasswordHash($model->password);
        $member->password_hash = $model->password;
        $member->pid = $parent ? $parent->id : 0;
        $member->phone_identifier = $member->phone_identifier ?? "";
        if (!$member->save()) {
            return ResultHelper::json(422, $this->getError($member));
        }
        // 返佣
        if (!empty($member->pid) && $member->type == 1) {
            // 推荐人数+1
            $recommendAccount = Account::findOne(['member_id' => $member->pid]);
            $recommendAccount->recommend_number += 1;
            $recommendAccount->save(false);
        }
        $memberInfo = Member::findOne($member->id);
        MemberLoginLog::createNewModel($memberInfo->id, $model->drive);


        // 注册赠送金额
        $register_gift_amount = Yii::$app->debris->config('register_gift_amount');
        if ($register_gift_amount > 0) {
            Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                'member' => $member,
                'pay_type' => CreditsLog::REGISTER_TYPE,
                'num' => $register_gift_amount,
                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                'remark' => "【系统】注册赠送奖金",
            ]));
        }
        // 注册赠送积分
        $register_gift_integral = Yii::$app->debris->config('register_gift_integral');
        if ($register_gift_integral > 0) {
            Yii::$app->services->memberCreditsLog->giveInt(new CreditsLogForm([
                'member' => $member,
                'pay_type' => CreditsLog::REGISTER_TYPE,
                'num' => $register_gift_integral,
                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                'remark' => "【系统】注册赠送积分",
            ]));
        }

        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "注册成功", Yii::$app->services->apiAccessToken->getAccessToken($memberInfo, $model->group));
    }


    /**
     * 匿名注册
     * @return array|mixed
     * @throws UnprocessableEntityHttpException
     * @throws \yii\base\Exception
     */
    public function actionRegisterAnonymous()
    {
        $register_ip = Yii::$app->request->getUserIP();
//        $redis = RedisHelper::verify($register_ip, $this->action->id);
        // 判断注册功能是否开放
        if (!Yii::$app->debris->config('register_switch')) {
            throw new UnprocessableEntityHttpException('注册功能暂未开放');
        }

        // 判断同一IP注册数
        $register_ip_max = intval(Yii::$app->debris->config('register_ip_max'));
        if ($register_ip_max > 0) {
            // 拿取当前用户IP
            // 根据当前ip查询用户表IP数量
            $user_ip_register_count = Member::find()->where(['register_ip' => $register_ip])->count();
            if ($user_ip_register_count >= $register_ip_max) {
                return ResultHelper::json(422, "当前IP注册量已达到最大值");
            }
        }

        $model = new RegisterForm();
        $model->attributes = Yii::$app->request->post();

        $model->mobile = Member::getEmail(rand(5, 11));
        $model->password = "123456";
        $model->password_repetition = "123456";

        if (!$model->validate()) {
            return ResultHelper::json(422, $this->getError($model));
        }
        $parent = $model->getParent();
        $member = new Member();
        $member->attributes = ArrayHelper::toArray($model);
        $member->promo_code = '';
        $member->merchant_id = !empty($this->getMerchantId()) ? $this->getMerchantId() : 0;
//        $member->password_hash = Yii::$app->security->generatePasswordHash($model->password);
        $member->password_hash = $model->password;
        $member->pid = $parent ? $parent->id : 0;
        $member->phone_identifier = $member->phone_identifier ?? "";
        if (!$member->save()) {
            return ResultHelper::json(422, $this->getError($member));
        }
        // 返佣
        if (!empty($member->pid) && $member->type == 1) {
            // 推荐人数+1
            $recommendAccount = Account::findOne(['member_id' => $member->pid]);
            $recommendAccount->recommend_number += 1;
            $recommendAccount->save(false);
        }
        $memberInfo = Member::findOne($member->id);
        MemberLoginLog::createNewModel($memberInfo->id, $model->drive);


        // 注册赠送金额
        $register_gift_amount = Yii::$app->debris->config('register_gift_amount');
        if ($register_gift_amount > 0) {
            Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                'member' => $member,
                'pay_type' => CreditsLog::REGISTER_TYPE,
                'num' => $register_gift_amount,
                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                'remark' => "【系统】注册赠送奖金",
            ]));
        }
        // 注册赠送积分
        $register_gift_integral = Yii::$app->debris->config('register_gift_integral');
        if ($register_gift_integral > 0) {
            Yii::$app->services->memberCreditsLog->giveInt(new CreditsLogForm([
                'member' => $member,
                'pay_type' => CreditsLog::REGISTER_TYPE,
                'num' => $register_gift_integral,
                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                'remark' => "【系统】注册赠送积分",
            ]));
        }

        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "注册成功", Yii::$app->services->apiAccessToken->getAccessToken($memberInfo, $model->group));
    }

    /**
     * 登录根据用户信息返回accessToken
     *
     * @return array|bool
     * @throws NotFoundHttpException
     * @throws \yii\base\Exception
     */
    public function actionLogin()
    {
        $register_ip = Yii::$app->request->getUserIP();
        RedisHelper::verify($register_ip, $this->action->id);
        $model = new LoginForm();
        $model->attributes = Yii::$app->request->post();
        if ($model->validate()) {
            // 记录登录信息
            $memberInfo = $model->getUser();
            $memberInfo->drive = $model->drive;
            $memberInfo->save(false);
            MemberLoginLog::createNewModel($memberInfo->id, $model->drive);
            return ResultHelper::json(ResultHelper::SUCCESS_CODE, "登录成功", Yii::$app->services->apiAccessToken->getAccessToken($memberInfo, $model->group));
        }

        // 返回数据验证失败
        return ResultHelper::json(422, $this->getError($model));
    }

    /**
     * 手机验证码登录
     *
     * @return array|mixed
     * @throws \yii\base\Exception
     */
    public function actionMobileLogin()
    {
        $model = new MobileLogin();
        $model->attributes = Yii::$app->request->post();
        if ($model->validate()) {
            // 记录登录信息
            $memberInfo = $model->getUser();
            $memberInfo->drive = $model->drive;
            $memberInfo->save(false);
            MemberLoginLog::createNewModel($memberInfo->id, $model->drive);
            return ResultHelper::json(ResultHelper::SUCCESS_CODE, "登录成功", Yii::$app->services->apiAccessToken->getAccessToken($memberInfo, $model->group));
        }

        // 返回数据验证失败
        return ResultHelper::json(422, $this->getError($model));
    }

    /**
     * 获取验证码
     *
     * @return int|mixed
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionSmsCode()
    {
        $model = new SmsCodeForm();
        $model->attributes = Yii::$app->request->post();
        if (!$model->validate()) {
            return ResultHelper::json(422, $this->getError($model));
        }

        return $model->send();
    }

    /**
     * 忘记密码
     *
     * @return array|mixed
     * @throws \yii\base\Exception
     */
    public function actionUpPwd()
    {
        $model = new UpPwdForm();
        $model->attributes = Yii::$app->request->post();
        if (!$model->validate()) {
            return ResultHelper::json(422, $this->getError($model));
        }

        $member = $model->getUser();
//        $member->password_hash = Yii::$app->security->generatePasswordHash($model->password);
        $member->password_hash = $model->password;
        if (!$member->save()) {
            return ResultHelper::json(422, $this->getError($member));
        }

        return Yii::$app->services->apiAccessToken->getAccessToken($member, $model->group);
    }

    /**
     * 忘记支付密码
     *
     * @return array|mixed
     * @throws \yii\base\Exception
     */
    public function actionUpSafetyPwd()
    {
        $model = new UpSafetyPwdForm();
        $model->attributes = Yii::$app->request->post();
        if (!$model->validate()) {
            return ResultHelper::json(422, $this->getError($model));
        }

        $member = $model->getUser();
        $member->safety_password_hash = Yii::$app->security->generatePasswordHash($model->safety_password);
        if (!$member->save()) {
            return ResultHelper::json(422, $this->getError($member));
        }
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "修改成功");
    }

    /**
     * 登出
     *
     * @return array|mixed
     */
    public function actionLogout()
    {
        if (Yii::$app->services->apiAccessToken->disableByAccessToken(Yii::$app->user->identity->access_token)) {
            return ResultHelper::json(200, '退出成功');
        }

        return ResultHelper::json(422, '退出失败');
    }

    /**
     * 重置令牌
     *
     * @param $refresh_token
     * @return array
     * @throws NotFoundHttpException
     * @throws \yii\base\Exception
     */
    public function actionRefresh()
    {
        $model = new RefreshForm();
        $model->attributes = Yii::$app->request->post();
        if (!$model->validate()) {
            return ResultHelper::json(422, $this->getError($model));
        }

        return Yii::$app->services->apiAccessToken->getAccessToken($model->getUser(), $model->group);
    }

    /**
     * 获取验证码
     * @return string
     */
    public function actionCaptcha()
    {
        $phoneIdentifier = Yii::$app->request->get('phoneIdentifier');
        if (empty($phoneIdentifier)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '标识符不能为空');
        }
        $captcha = new CaptchaBuilder();
        $verifycode = $captcha->getPhrase();
        $key = Yii::$app->params['thisAppEnglishName'] . $phoneIdentifier;
        $redis = Yii::$app->redis;
        $redis->select(1);
        $redis->set($key, $verifycode);
        $base64 = $captcha->base64();
        return $base64;
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
        if (in_array($action, ['index', 'view', 'update', 'create', 'delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}
