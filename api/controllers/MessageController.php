<?php

namespace api\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * 报错消息处理
 *
 * Class MessageController
 * @package api\controllers
 * @author 原创脉冲
 */
class MessageController extends Controller
{
    /**
     * @return string
     */
    public function actionError()
    {
        if (($exception = Yii::$app->getErrorHandler()->exception) === null) {
            $exception = new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }

        return $exception->getMessage();
    }
}
