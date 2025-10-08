<?php

namespace backend\controllers;

use common\helpers\StringHelper;
use common\models\backend\Member;
use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;
use common\traits\BaseAction;
use common\helpers\Auth;
use common\behaviors\ActionLogBehavior;

/**
 * Class BaseController
 * @package backend\controllers
 * @author 原创脉冲
 */
class BaseController extends Controller
{
    use BaseAction;

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // 登录
                    ],
                ],
            ],
            'actionLog' => [
                'class' => ActionLogBehavior::class
            ]
        ];
    }

    /**
     * @param \yii\base\Action $action
     * @return bool
     * @throws ForbiddenHttpException
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\UnauthorizedHttpException
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // 开始IP校验
        if (!self::ipVerify()) {
            Yii::$app->user->logout();
            throw new ForbiddenHttpException('对不起，您现在还没获此操作的权限~');
        }

        // 每页数量
        $this->pageSize = Yii::$app->request->get('per-page', 10);
        $this->pageSize > 200 && $this->pageSize = 200;

        // 判断当前模块的是否为主模块, 模块+控制器+方法
        $permissionName = '/' . Yii::$app->controller->route;
        // 判断是否忽略校验
        if (in_array($permissionName, Yii::$app->params['noAuthRoute'])) {
            return true;
        }
        // 开始权限校验
        if (!Auth::verify($permissionName)) {
            throw new ForbiddenHttpException('对不起，您现在还没获此操作的权限');
        }




        // 验证session
//        if (Yii::$app->debris->config('backend_login_one_switch') && Yii::$app->user->id != Yii::$app->params['adminAccount']) {
        if (Yii::$app->debris->config('backend_login_one_switch')) {
            $memberInfo = Member::findOne(Yii::$app->user->id);
            $last_session = Yii::$app->session->get("session_" . Yii::$app->user->identity->username);
            if ($last_session != $memberInfo->last_session) { //进行比较每次请求传递来的和数据库存储的session是否一致
                Yii::$app->user->logout();
                throw new ForbiddenHttpException('您的账号已在其他地方登录~');
            }
        }

        // 记录上一页跳转
        $this->setReferrer($action->id);

        return true;
    }

    public static function ipVerify()
    {
        $ip = Yii::$app->request->userIP;
        $allowIp = Yii::$app->debris->backendConfig('sys_allow_ip');
        if (!empty($allowIp)) {
            $ipList = StringHelper::parseAttr($allowIp);
            if (!in_array($ip, $ipList)) {
                return false;
            }
            return true;
        }
        return true;
    }
}