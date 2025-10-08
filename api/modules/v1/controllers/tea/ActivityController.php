<?php

namespace api\modules\v1\controllers\tea;

use api\controllers\OnAuthController;
use common\enums\StatusEnum;
use common\models\tea\Activity;

class ActivityController extends OnAuthController
{
    public $modelClass = '';
    // 不用进行登录验证的方法
    protected $authOptional = ['index'];

    public function actionIndex()
    {
        return Activity::find()
            ->select(['id', 'title', 'open_time', 'stop_time'])
            ->where(['status' => StatusEnum::ENABLED])
            ->asArray()
            ->all();
    }

    /**
     * 权限验证
     * @param string $action
     * @param null $model
     * @param array $params
     * @throws \yii\web\BadRequestHttpException
     * @author 原创脉冲
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        // 方法名称
        if (in_array($action, ['view', 'update', 'create', 'delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }


}