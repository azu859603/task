<?php

namespace api\modules\v1\controllers\member;

use api\controllers\OnAuthController;
use common\enums\StatusEnum;
use common\helpers\CommonPluginHelper;
use common\helpers\RedisHelper;
use common\helpers\RedisLock;
use common\helpers\ResultHelper;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use common\models\member\RealnameAudit;
use Yii;
use yii\web\UnprocessableEntityHttpException;

class RealnameAuditController extends OnAuthController
{
    public $modelClass = RealnameAudit::class;

    /**
     * 添加实名认证
     * @return array|mixed|\yii\db\ActiveRecord
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionCreate()
    {
        RedisHelper::verify($this->memberId, $this->action->uniqueId);
        if (RealnameAudit::find()->where(['member_id' => $this->memberId, 'status' => StatusEnum::DISABLED])->exists()) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '您的申请正在审核中，请耐心等待');
        }
        if (Member::find()->where(['identification_number' => Yii::$app->request->post('identification_number')])->exists()) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '该证件号码已存在');
        }

        // 自动实名认证
        $member = Member::findOne($this->memberId);
        if ($member->realname != "" || $member->realname_status == 1) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "您已完成实名认证审核，请勿重复提交");
        }
        $identification_number = trim(Yii::$app->request->post('identification_number'));
        $realname = trim(Yii::$app->request->post('realname'));
        $appcode = Yii::$app->debris->config('app_code');
        // 联卓
//        $url = "http://lundroid.com/composite/idvertify";
//        $data = [
//            'id' => $identification_number,
//            'name' => $realname,
//            'appkey' => $appcode,
//        ];
//        $data = http_build_query($data);
//        $url = $url . "?" . $data;
//        $result = json_decode(CommonPluginHelper::curl_get($url), true);
//        if (empty($result)) {
//            return ResultHelper::json(ResultHelper::ERROR_CODE, "实名认证失败,请稍后再试");
//        }

//        // 阿里云
//        $url = "https://idcard.market.alicloudapi.com/lianzhuo/idcard";
//        $data = [
//            'cardno' => $identification_number,
//            'name' => $realname,
//        ];
//        $data = http_build_query($data);
//        $url = $url . "?" . $data;
//        $headers = array();
//        array_push($headers, "Authorization:APPCODE " . $appcode);
//        $result = json_decode(CommonPluginHelper::curl_get($url, $headers), true);
//        if (empty($result)) {
//            return ResultHelper::json(ResultHelper::ERROR_CODE, "实名认证失败,请稍后再试");
//        }
//
//        if ($result['resp']['code'] == "0") {
//            $model = new RealnameAudit();
//            if ($model->load(Yii::$app->request->post(), '') && $model->validate()) {
//                $model->status = 1; // 自动实名认证
//                $model->save(false);
//                $member->realname_status = 1;
//                $member->realname = $realname;
//                $member->identification_number = $identification_number;
//                $member->save(false);
//
//                // 赠送奖金
//                $realname_send_money = Yii::$app->debris->config('realname_send_money');
//                if ($realname_send_money > 0) {
//                    Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
//                        'member' => $member,
//                        'pay_type' => CreditsLog::GIFT_TYPE,
//                        'num' => $realname_send_money,
//                        'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
//                        'remark' => "【系统】完成实名认证获得奖励",
//                    ]));
//                }
//
//                return ResultHelper::json(ResultHelper::SUCCESS_CODE, "提交成功！");
//            } else {
//                $error = array_values($model->errors) ? array_values($model->errors) : [['系统繁忙,请稍后再试']];
//                return ResultHelper::json(ResultHelper::ERROR_CODE, $error[0][0]);
//            }
//        } else {
//            return ResultHelper::json(ResultHelper::ERROR_CODE, "请输入真实的姓名和身份证号码");
//        }

        $model = new RealnameAudit();
        if ($model->load(Yii::$app->request->post(), '') && $model->validate()) {
            $model->save(false);

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

            return ResultHelper::json(ResultHelper::SUCCESS_CODE, "提交成功");
        } else {
            $error = array_values($model->errors) ? array_values($model->errors) : [['系统繁忙,请稍后再试']];
            return ResultHelper::json(ResultHelper::ERROR_CODE, $error[0][0]);
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
        if (in_array($action, ['index', 'view', 'update', 'delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}